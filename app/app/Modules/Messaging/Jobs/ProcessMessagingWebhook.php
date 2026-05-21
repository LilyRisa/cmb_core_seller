<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Xử lý 1 webhook_event đã lưu (provider prefix `messaging.<code>`).
 * Resolve channel_account + parse webhook → upsert qua MessageIngestionService.
 *
 * Idempotent: WebhookIngestService đã dedupe theo `(provider, external_id, event_type)`;
 * job re-run vẫn an toàn vì MessageIngestionService dedupe ở
 * `(conversation_id, external_message_id)`.
 *
 * Queue: `messaging-webhooks` (supervisor `critical` — ưu tiên cao như webhook sàn).
 */
class ProcessMessagingWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $webhookEventId)
    {
        $this->onQueue('messaging-webhooks');
    }

    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    public function handle(MessagingRegistry $registry, MessageIngestionService $ingest): void
    {
        $event = WebhookEvent::find($this->webhookEventId);
        if (! $event || $event->status === WebhookEvent::STATUS_PROCESSED) {
            return;
        }
        $event->forceFill(['attempts' => ($event->attempts ?? 0) + 1])->save();

        $messagingCode = $this->messagingProviderCode($event->provider);

        if (! $messagingCode || ! $registry->has($messagingCode)) {
            $event->markProcessed(WebhookEvent::STATUS_IGNORED);
            return;
        }
        $connector = $registry->for($messagingCode);

        // Resolve shop (cross-tenant; webhook không có tenant context).
        // Map messaging code → channel provider để lookup `channel_accounts`.
        $channelProvider = $this->channelProviderForMessaging($messagingCode);
        $shopId = $event->external_shop_id;
        $account = $shopId
            ? ChannelAccount::withoutGlobalScope(TenantScope::class)
                ->where('provider', $channelProvider)
                ->where('external_shop_id', $shopId)
                ->first()
            : null;

        if (! $account) {
            Log::warning('messaging.webhook.shop_not_found', [
                'event_id' => $event->id,
                'provider' => $event->provider,
                'shop' => $shopId,
            ]);
            $event->markProcessed(WebhookEvent::STATUS_IGNORED);
            return;
        }

        // Re-parse từ payload đã lưu (Request không còn ở job time)
        $dto = $this->rebuildDtoFromStoredPayload($event);
        if (! $dto || ! $dto->externalConversationId || ! $dto->externalMessageId) {
            $event->markProcessed(WebhookEvent::STATUS_IGNORED);
            return;
        }

        $msgDto = $this->messagingDtoFromWebhook($dto);
        if (! $msgDto) {
            // Event không phải message_received (vd typing/read receipt) — đã ack, không ingest.
            $event->markProcessed(WebhookEvent::STATUS_PROCESSED);
            return;
        }

        $result = $ingest->ingest($account, $msgDto);
        $ingest->fireEventsForNewMessage(
            $result['conversation'],
            $result['message'],
            isNewConversation: $result['conversation']->wasRecentlyCreated,
        );

        $event->markProcessed(WebhookEvent::STATUS_PROCESSED);
    }

    /** Extract messaging code từ `provider` field (vd `messaging.facebook_page` → `facebook_page`). */
    private function messagingProviderCode(string $provider): ?string
    {
        if (! str_starts_with($provider, 'messaging.')) {
            return null;
        }
        return substr($provider, strlen('messaging.'));
    }

    /**
     * Map messaging connector code → ChannelAccount provider để lookup shop.
     * Shopee/TikTok/Lazada chia chung token với Channels orders, nên messaging
     * code khác channel provider code. Facebook Page và Manual: 1-1.
     */
    private function channelProviderForMessaging(string $messagingCode): string
    {
        return match ($messagingCode) {
            'tiktok_chat' => 'tiktok',
            'shopee_chat' => 'shopee',
            'lazada_chat' => 'lazada',
            'facebook_page' => 'facebook_page',
            'manual' => 'manual',
            default => $messagingCode,
        };
    }

    /**
     * Rebuild MessagingWebhookEventDTO từ row `webhook_events` (payload đã lưu).
     * Không cần Request nguyên gốc — `payload` đã JSON serialized.
     *
     * Reads normalized _kind/_body/_attachments keys written by MessagingWebhookIngestService
     * (Phase B). Falls back gracefully when keys absent (legacy rows / manual connector).
     */
    private function rebuildDtoFromStoredPayload(WebhookEvent $event): ?MessagingWebhookEventDTO
    {
        $payload = is_array($event->payload) ? $event->payload : [];

        // Rebuild kind from _kind (Phase B normalized key).
        $kind = isset($payload['_kind']) ? MessageKind::tryFrom((string) $payload['_kind']) : null;

        // Rebuild attachments from _attachments array (Phase B).
        $attachments = [];
        if (! empty($payload['_attachments']) && is_array($payload['_attachments'])) {
            foreach ($payload['_attachments'] as $a) {
                $attachmentKind = MessageKind::tryFrom((string) ($a['kind'] ?? ''));
                if ($attachmentKind === null) {
                    continue;
                }
                $attachments[] = new MediaRefDTO(
                    kind: $attachmentKind,
                    mime: $a['mime'] ?? 'application/octet-stream',
                    sizeBytes: isset($a['size_bytes']) ? (int) $a['size_bytes'] : null,
                    externalUrl: $a['external_url'] ?? null,
                    storagePath: $a['storage_path'] ?? null,
                    filename: $a['filename'] ?? null,
                    width: isset($a['width']) ? (int) $a['width'] : null,
                    height: isset($a['height']) ? (int) $a['height'] : null,
                    durationMs: isset($a['duration_ms']) ? (int) $a['duration_ms'] : null,
                );
            }
        }

        return new MessagingWebhookEventDTO(
            provider: $this->messagingProviderCode($event->provider) ?? $event->provider,
            type: (string) ($event->event_type ?: MessagingWebhookEventDTO::TYPE_UNKNOWN),
            externalShopId: $event->external_shop_id ?: null,
            externalConversationId: isset($payload['external_conversation_id']) ? (string) $payload['external_conversation_id'] : null,
            externalMessageId: isset($payload['external_message_id']) ? (string) $payload['external_message_id'] : null,
            buyerExternalId: isset($payload['buyer_external_id']) ? (string) $payload['buyer_external_id'] : null,
            raw: $payload,
            kind: $kind,
            body: isset($payload['_body']) ? (string) $payload['_body'] : null,
            attachments: $attachments,
        );
    }

    /**
     * Chuyển webhook event → MessageDTO. Trả null nếu event không cần ingest
     * (typing, read_receipt, conversation_closed — handle riêng phase sau).
     */
    private function messagingDtoFromWebhook(MessagingWebhookEventDTO $event): ?MessageDTO
    {
        if (! in_array($event->type, [
            MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            MessagingWebhookEventDTO::TYPE_UNKNOWN, // manual connector default
        ], true)) {
            return null;
        }
        if (! $event->externalConversationId || ! $event->externalMessageId || ! $event->buyerExternalId) {
            return null;
        }

        $payload = $event->raw;

        // Use normalized kind from DTO (Phase B connectors set this), falling back to
        // legacy payload['kind'] key (manual connector) and finally defaulting to Text.
        $kind = $event->kind
            ?? MessageKind::tryFrom((string) ($payload['kind'] ?? 'text'))
            ?? MessageKind::Text;

        // Use normalized body from DTO (Phase B), falling back to legacy payload['body'].
        $body = $event->body ?? (isset($payload['body']) ? (string) $payload['body'] : null);

        return new MessageDTO(
            externalConversationId: $event->externalConversationId,
            externalMessageId: $event->externalMessageId,
            buyerExternalId: $event->buyerExternalId,
            direction: MessageDirection::Inbound,
            kind: $kind,
            body: $body,
            attachments: $event->attachments, // Phase B: connectors populate; manual/legacy → []
            sentAt: $event->occurredAt ?? \Carbon\CarbonImmutable::now(),
            raw: $payload,
        );
    }
}
