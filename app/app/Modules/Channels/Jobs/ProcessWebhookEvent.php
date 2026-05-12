<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountRevoked;
use CMBcoreSeller\Modules\Channels\Events\DataDeletionRequested;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes one stored webhook_event: resolve tenant + channel account, then
 * (for order events) re-fetch the order detail and upsert it — the webhook is a
 * signal, never the source of truth. Idempotent. queue: webhooks.
 * See docs/03-domain/order-sync-pipeline.md §2, docs/05-api/webhooks-and-oauth.md §1.
 */
class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $webhookEventId)
    {
        $this->onQueue('webhooks');
    }

    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    public function handle(ChannelRegistry $registry, OrderUpsertService $upsert, TokenRefresher $tokens): void
    {
        /** @var WebhookEvent|null $event */
        $event = WebhookEvent::find($this->webhookEventId);
        if (! $event || $event->status === WebhookEvent::STATUS_PROCESSED) {
            return;
        }
        $event->forceFill(['attempts' => ($event->attempts ?? 0) + 1])->save();

        if (! $registry->has($event->provider)) {
            $event->markProcessed(WebhookEvent::STATUS_IGNORED);

            return;
        }
        $connector = $registry->for($event->provider);

        // Resolve the shop. Webhooks carry no tenant context (rule 7 in multi-tenancy doc).
        $account = $event->external_shop_id
            ? ChannelAccount::withoutGlobalScope(TenantScope::class)
                ->where('provider', $event->provider)->where('external_shop_id', $event->external_shop_id)->first()
            : null;

        if (! $account) {
            Log::info('webhook.shop_not_found', ['provider' => $event->provider, 'shop' => $event->external_shop_id, 'event_type' => $event->event_type]);
            $event->forceFill(['status' => WebhookEvent::STATUS_IGNORED, 'error' => 'channel account not found'])->save();
            $event->markProcessed(WebhookEvent::STATUS_IGNORED);

            return;
        }

        $event->forceFill(['tenant_id' => $account->tenant_id, 'channel_account_id' => $account->getKey()])->save();
        $account->forceFill(['last_webhook_at' => now()])->save();

        try {
            match ($event->event_type) {
                WebhookEventDTO::TYPE_ORDER_CREATED,
                WebhookEventDTO::TYPE_ORDER_STATUS_UPDATE,
                WebhookEventDTO::TYPE_ORDER_CANCEL => $this->handleOrderEvent($event, $account, $connector, $upsert, $tokens),

                WebhookEventDTO::TYPE_SHOP_DEAUTHORIZED => $this->handleDeauthorized($account),

                WebhookEventDTO::TYPE_DATA_DELETION => DataDeletionRequested::dispatch($account), // Customers listens → anonymize buyer PII (SPEC 0002 §8)

                WebhookEventDTO::TYPE_RETURN_UPDATE,
                WebhookEventDTO::TYPE_SETTLEMENT_AVAILABLE,
                WebhookEventDTO::TYPE_PRODUCT_UPDATE => Log::info('webhook.deferred', ['type' => $event->event_type, 'shop' => $account->external_shop_id]), // later phases

                default => $event->forceFill(['status' => WebhookEvent::STATUS_IGNORED])->save(),
            };
        } catch (Throwable $e) {
            // Let the job retry; on the final attempt mark failed.
            if ($this->attempts() >= $this->tries) {
                $event->markFailed($e->getMessage());
            }
            throw $e;
        }

        if ($event->status !== WebhookEvent::STATUS_IGNORED) {
            $event->markProcessed();
        }
    }

    private function handleOrderEvent(WebhookEvent $event, ChannelAccount $account, $connector, OrderUpsertService $upsert, TokenRefresher $tokens): void
    {
        $orderId = $event->external_id ?: ($event->payload['data']['order_id'] ?? null);
        if (! $orderId) {
            $event->forceFill(['status' => WebhookEvent::STATUS_IGNORED, 'error' => 'no order id in event'])->save();

            return;
        }

        try {
            $dto = $connector->fetchOrderDetail($account->authContext(), (string) $orderId);
        } catch (Throwable $e) {
            $authErr = method_exists($e, 'isAuthError') ? $e->isAuthError() : str_contains(strtolower($e->getMessage()), 'access_token');
            if ($authErr && $tokens->refresh($account)) {
                $dto = $connector->fetchOrderDetail($account->fresh()->authContext(), (string) $orderId);
            } else {
                throw $e;
            }
        }

        $status = $connector->mapStatus($dto->rawStatus, $dto->raw);
        $upsert->upsertWithStatus($dto, (int) $account->tenant_id, (int) $account->getKey(), 'webhook', $status);
    }

    private function handleDeauthorized(ChannelAccount $account): void
    {
        if ($account->status !== ChannelAccount::STATUS_REVOKED) {
            $account->forceFill(['status' => ChannelAccount::STATUS_REVOKED])->save();
            ChannelAccountRevoked::dispatch($account, 'seller deauthorized (webhook)');
        }
    }
}
