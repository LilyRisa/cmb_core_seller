<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
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

    public function handle(MessagingRegistry $registry, MessageIngestionService $ingest, CommentConversationUpserter $commentUpserter): void
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

        // ── Reaction event: update meta['reaction'] on the target message ────────
        if ($dto->type === MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION) {
            $this->applyReaction($dto, $account);
            $event->markProcessed(WebhookEvent::STATUS_PROCESSED);

            return;
        }

        $msgDto = $this->messagingDtoFromWebhook($dto);
        if (! $msgDto) {
            // Event không phải message_received (vd typing/read receipt) — đã ack, không ingest.
            $event->markProcessed(WebhookEvent::STATUS_PROCESSED);

            return;
        }

        // Comment thread: upsert the comment-conversation BEFORE calling ingest so
        // MessageIngestionService finds an existing comment-conversation instead of
        // creating a default message-conversation.
        $isComment = $dto->threadType === Conversation::THREAD_COMMENT;
        if ($isComment) {
            $commentUpserter->upsert($account, array_merge([
                'top_level_comment_id' => $dto->externalConversationId,
                'buyer_external_id' => $dto->buyerExternalId ?? '',
                'occurred_at' => $dto->occurredAt,
            ], $dto->threadMeta));
        }

        $result = $ingest->ingest($account, $msgDto);

        // For comment threads: do NOT fire MessageReceived auto-reply event — page
        // replies to comments are manual (not bot). Media relay still runs if needed.
        $ingest->fireEventsForNewMessage(
            $result['conversation'],
            $result['message'],
            isNewConversation: $result['conversation']->wasRecentlyCreated,
            fireInboundEvent: ! $isComment,
        );

        // Sync hồ sơ buyer (tên + avatar) cho DM còn thiếu — webhook tạo conversation
        // KHÔNG fetch profile (khác backfill). Job tự throttle 24h tránh spam Graph.
        if (! $isComment) {
            $this->maybeSyncBuyerProfile($result['conversation']);
        }

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

        // Rebuild thread context (Phase C) — persisted by MessagingWebhookIngestService.
        $threadType = isset($payload['_thread_type']) && is_string($payload['_thread_type'])
            ? $payload['_thread_type']
            : null;
        $threadMeta = isset($payload['_thread_meta']) && is_array($payload['_thread_meta'])
            ? $payload['_thread_meta']
            : [];

        // Direction + structured meta (vd nút bấm) — persisted by MessagingWebhookIngestService.
        $direction = isset($payload['_direction'])
            ? (MessageDirection::tryFrom((string) $payload['_direction']) ?? MessageDirection::Inbound)
            : MessageDirection::Inbound;
        $meta = isset($payload['_meta']) && is_array($payload['_meta']) ? $payload['_meta'] : [];

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
            threadType: $threadType,
            threadMeta: $threadMeta,
            direction: $direction,
            meta: $meta,
        );
    }

    /**
     * Apply a Facebook reaction (react|unreact) to the target message.
     * Finds the message by external_message_id within the conversation identified
     * by (channel_account_id, external_conversation_id = sender PSID).
     * If the target message is not found (arrived before the message, or too old),
     * we silently skip — no error, the event is still marked processed.
     */
    private function applyReaction(MessagingWebhookEventDTO $dto, ChannelAccount $account): void
    {
        $raw = $dto->raw;
        $reaction = is_array($raw['reaction'] ?? null) ? (array) $raw['reaction'] : [];
        $action = (string) ($reaction['action'] ?? 'react');
        $emoji = isset($reaction['emoji']) ? (string) $reaction['emoji'] : null;
        $mid = $dto->externalMessageId;
        $psid = $dto->externalConversationId;

        if (! $mid || ! $psid) {
            return;
        }

        // Find the conversation for this (account, PSID) pair.
        $conversation = Conversation::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $account->getKey())
            ->where('external_conversation_id', $psid)
            ->first();

        if (! $conversation) {
            return; // Conversation not yet created — skip gracefully.
        }

        // Find the target message by its external_message_id within the conversation.
        $message = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conversation->getKey())
            ->where('external_message_id', $mid)
            ->first();

        if (! $message) {
            return; // Message not yet ingested or too old — skip gracefully.
        }

        $meta = is_array($message->meta) ? $message->meta : [];

        if ($action === 'react' && $emoji !== null) {
            $meta['reaction'] = $emoji;
        } else {
            unset($meta['reaction']);
        }

        $message->meta = $meta;
        $message->save();
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
            // Direction từ DTO (echo Facebook = outbound); mặc định Inbound cho mọi
            // connector cũ/manual không set ⇒ không đổi hành vi hiện có.
            direction: $event->direction,
            kind: $kind,
            body: $body,
            attachments: $event->attachments, // Phase B: connectors populate; manual/legacy → []
            sentAt: $event->occurredAt ?? CarbonImmutable::now(),
            raw: $payload,
            meta: $event->meta,
        );
    }

    /**
     * Dispatch SyncConversationProfile khi conversation DM còn thiếu avatar và chưa
     * thử fetch trong 24h gần đây (đọc mốc `meta.profile_attempted_at` để không
     * đẩy job thừa mỗi tin khi page chưa được duyệt quyền lấy profile buyer).
     */
    private function maybeSyncBuyerProfile(Conversation $conversation): void
    {
        if ($conversation->buyer_avatar_path !== null) {
            return;
        }

        $meta = is_array($conversation->meta) ? $conversation->meta : [];
        $attemptedAt = isset($meta['profile_attempted_at'])
            ? CarbonImmutable::parse((string) $meta['profile_attempted_at'])
            : null;
        if ($attemptedAt !== null && $attemptedAt->gt(now()->subDay())) {
            return;
        }

        SyncConversationProfile::dispatch((int) $conversation->id);
    }
}
