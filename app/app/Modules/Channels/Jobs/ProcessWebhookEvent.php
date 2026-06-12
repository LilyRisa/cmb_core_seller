<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\PenaltyWebhookConnector;
use CMBcoreSeller\Integrations\Channels\DTO\PenaltyEventDTO;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountRevoked;
use CMBcoreSeller\Modules\Channels\Events\DataDeletionRequested;
use CMBcoreSeller\Modules\Channels\Events\MarketplaceProductUpdated;
use CMBcoreSeller\Modules\Channels\Events\ShopPenaltyDetected;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\ShopPenaltyEvent;
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
                WebhookEventDTO::TYPE_ORDER_STATUS_UPDATE => $this->handleOrderEvent($event, $account, $connector, $upsert, $tokens),

                // Hủy: cập nhật order (→ cancelled) NHƯNG cũng đồng bộ bản ghi hủy (after-sales). SPEC 0025.
                WebhookEventDTO::TYPE_ORDER_CANCEL => $this->handleCancelEvent($event, $account, $connector, $upsert, $tokens),

                // Hoàn/trả: webhook là tín hiệu ⇒ re-poll after-sales của shop (poll là nguồn truth). SPEC 0025.
                WebhookEventDTO::TYPE_RETURN_UPDATE => $this->handleAfterSales($account, $connector),

                WebhookEventDTO::TYPE_SHOP_DEAUTHORIZED => $this->handleDeauthorized($account),

                WebhookEventDTO::TYPE_DATA_DELETION => DataDeletionRequested::dispatch($account), // Customers listens → anonymize buyer PII (SPEC 0002 §8)

                WebhookEventDTO::TYPE_SHOP_PENALTY_UPDATE => $this->handlePenaltyUpdate($event, $account, $connector),

                // Sản phẩm đổi (kết thúc xét duyệt QC / bị cấm) ⇒ Products re-check trạng thái
                // các nháp đang chờ duyệt trên shop (webhook = tín hiệu, poll lại là nguồn truth).
                WebhookEventDTO::TYPE_PRODUCT_UPDATE => MarketplaceProductUpdated::dispatch($account),

                WebhookEventDTO::TYPE_SETTLEMENT_AVAILABLE => Log::info('webhook.deferred', ['type' => $event->event_type, 'shop' => $account->external_shop_id]), // later phases

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

    /**
     * Điểm phạt/vi phạm (Shopee penalty/violation push): bóc qua connector (segregated capability),
     * lưu {@see ShopPenaltyEvent} + phát {@see ShopPenaltyDetected} để cảnh báo. Core không biết shape sàn.
     */
    private function handlePenaltyUpdate(WebhookEvent $event, ChannelAccount $account, $connector): void
    {
        if (! $connector instanceof PenaltyWebhookConnector) {
            $event->forceFill(['status' => WebhookEvent::STATUS_IGNORED])->save();

            return;
        }
        $parsed = $connector->parsePenaltyWebhook((array) $event->payload);
        if ($parsed === []) {
            $event->forceFill(['status' => WebhookEvent::STATUS_IGNORED])->save();

            return;
        }
        foreach ($parsed as $dto) {
            /** @var PenaltyEventDTO $dto */
            $row = ShopPenaltyEvent::create([
                'tenant_id' => (int) $account->tenant_id,
                'channel_account_id' => (int) $account->getKey(),
                'provider' => $account->provider,
                'kind' => $dto->kind,
                'points' => $dto->points,
                'violation_type' => $dto->violationType,
                'violation_label' => $dto->violationLabel,
                'tier' => $dto->tier,
                'item_id' => $dto->itemId,
                'item_name' => $dto->itemName,
                'webhook_event_id' => (int) $event->getKey(),
                'occurred_at' => $dto->occurredAt,
                'raw' => $dto->raw ?: null,
            ]);
            ShopPenaltyDetected::dispatch($row, $account);
        }
        Log::info('webhook.shop_penalty_recorded', ['shop' => $account->external_shop_id, 'count' => count($parsed)]);
    }

    private function handleOrderEvent(WebhookEvent $event, ChannelAccount $account, $connector, OrderUpsertService $upsert, TokenRefresher $tokens): void
    {
        $orderId = $event->external_id ?: ($event->payload['data']['order_id'] ?? null);
        if (! $orderId) {
            $event->forceFill(['status' => WebhookEvent::STATUS_IGNORED, 'error' => 'no order id in event'])->save();

            return;
        }
        $orderId = (string) $orderId;

        // Fast path: the push carried the new status — apply it straight away to an
        // existing order so the webhook "works" even if the API re-fetch is flaky.
        $appliedFromPayload = false;
        if ($event->order_raw_status) {
            $status = $connector->mapStatus($event->order_raw_status, []);
            if ($upsert->applyStatusFromWebhook((int) $account->tenant_id, (int) $account->getKey(), $event->provider, $orderId, $status, $event->order_raw_status) !== null) {
                $appliedFromPayload = true;
            }
        }

        // Enrich (and create the order if we didn't have it) by re-fetching the full detail.
        try {
            $dto = $connector->fetchOrderDetail($account->authContext(), $orderId);
        } catch (Throwable $e) {
            $authErr = method_exists($e, 'isAuthError') ? $e->isAuthError() : str_contains(strtolower($e->getMessage()), 'access_token');
            if ($authErr && $tokens->refresh($account)) {
                $dto = $connector->fetchOrderDetail($account->fresh()->authContext(), $orderId);
            } elseif ($appliedFromPayload) {
                Log::warning('webhook.detail_fetch_failed_status_applied', ['provider' => $event->provider, 'order' => $orderId, 'status' => $event->order_raw_status, 'error' => $e->getMessage()]);

                return; // status is already updated; polling will fill in the rest
            } else {
                throw $e;
            }
        }

        $status = $connector->mapStatus($dto->rawStatus, $dto->raw);
        $upsert->upsertWithStatus($dto, (int) $account->tenant_id, (int) $account->getKey(), 'webhook', $status);
    }

    /** Hủy đơn: cập nhật trạng thái order (re-fetch) + đồng bộ bản ghi hủy (after-sales). SPEC 0025. */
    private function handleCancelEvent(WebhookEvent $event, ChannelAccount $account, $connector, OrderUpsertService $upsert, TokenRefresher $tokens): void
    {
        $this->handleOrderEvent($event, $account, $connector, $upsert, $tokens);
        $this->handleAfterSales($account, $connector);
    }

    /** Webhook hoàn/hủy là tín hiệu ⇒ dispatch SyncReturnsForShop (poll là nguồn truth). No-op nếu connector chưa hỗ trợ. */
    private function handleAfterSales(ChannelAccount $account, $connector): void
    {
        if ($connector->supports('returns.fetch')) {
            SyncReturnsForShop::dispatch((int) $account->getKey());
        }
    }

    private function handleDeauthorized(ChannelAccount $account): void
    {
        if ($account->status !== ChannelAccount::STATUS_REVOKED) {
            $account->forceFill(['status' => ChannelAccount::STATUS_REVOKED])->save();
            ChannelAccountRevoked::dispatch($account, 'seller deauthorized (webhook)');
        }
    }
}
