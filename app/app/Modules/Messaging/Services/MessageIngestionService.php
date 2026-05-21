<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\ConversationCreated;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Upsert idempotent inbound messages từ webhook hoặc polling.
 *
 * Idempotency: UNIQUE `(conversation_id, external_message_id)` ở DB chống
 * duplicate khi webhook + polling cùng về. Service-level chạy trong transaction
 * + `lockForUpdate` trên conversation để tránh race count/preview drift.
 *
 * SPEC-0024 §4.1.
 *
 * Mirror pattern `OrderUpsertService` (Phase 1) — single entry point cho mọi
 * inbound flow, không có 2 codepath song song.
 */
class MessageIngestionService
{
    /**
     * Upsert 1 inbound message vào DB. Tạo conversation nếu chưa có.
     *
     * Trả `['conversation' => Conversation, 'message' => Message, 'created' => bool]`.
     * `created=false` nếu message đã tồn tại (dedupe).
     *
     * @return array{conversation: Conversation, message: Message, created: bool}
     */
    public function ingest(ChannelAccount $channelAccount, MessageDTO $dto): array
    {
        return DB::transaction(function () use ($channelAccount, $dto) {
            $conversation = $this->ensureConversation($channelAccount, $dto);

            // Dedupe: tìm theo (conversation_id, external_message_id).
            // withoutGlobalScope(TenantScope): service chạy trong job/webhook
            // KHÔNG có tenant context — tenant lấy từ $channelAccount.
            $existing = Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conversation->id)
                ->where('external_message_id', $dto->externalMessageId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return ['conversation' => $conversation, 'message' => $existing, 'created' => false];
            }

            $message = Message::create([
                'tenant_id' => $channelAccount->tenant_id,
                'conversation_id' => $conversation->id,
                'external_message_id' => $dto->externalMessageId,
                'direction' => $dto->direction->value,
                'kind' => $dto->kind->value,
                'body' => $dto->body,
                'attachments_count' => count($dto->attachments),
                // Delivery status: inbound luôn 'sent' (tin đã tới buyer khi sàn push về app);
                // outbound (echo-back) cũng 'sent' — chỉ outbound mới do app gửi mới start 'pending'.
                'delivery_status' => Message::STATUS_SENT,
                'sent_at' => $dto->sentAt,
                'delivered_at' => $dto->deliveredAt,
                'read_at' => $dto->readAt,
                'raw_payload' => $dto->raw,
            ]);

            foreach ($dto->attachments as $media) {
                MessageAttachment::create([
                    'tenant_id' => $channelAccount->tenant_id,
                    'message_id' => $message->id,
                    'kind' => $media->kind->value,
                    'mime' => $media->mime,
                    'size_bytes' => $media->sizeBytes,
                    'external_url' => $media->externalUrl,
                    'storage_path' => $media->storagePath,
                    'filename' => $media->filename,
                    'width' => $media->width,
                    'height' => $media->height,
                    'duration_ms' => $media->durationMs,
                    'status' => $media->storagePath
                        ? MessageAttachment::STATUS_DOWNLOADED
                        : MessageAttachment::STATUS_PENDING,
                ]);
            }

            // Cập nhật conversation header. Lock đã ở `ensureConversation` ⇒ no race.
            $this->updateConversationOnNewMessage($conversation, $message);

            return ['conversation' => $conversation, 'message' => $message, 'created' => true];
        });
    }

    /**
     * Fire event sau khi transaction commit (idempotent với DB row đã tạo).
     * Tách khỏi `ingest` để caller (webhook job) tự kiểm `created` flag rồi mới fire.
     *
     * @param  bool  $fireInboundEvent  Khi `false`, bỏ qua `MessageReceived` (dùng cho lần sync
     *                                  đầu tiên của Lazada để không auto-reply toàn bộ backlog
     *                                  lịch sử). Media relay và `ConversationCreated` vẫn chạy
     *                                  bình thường. Mặc định `true` — webhook path không đổi.
     */
    public function fireEventsForNewMessage(Conversation $conversation, Message $message, bool $isNewConversation, bool $fireInboundEvent = true): void
    {
        if ($isNewConversation) {
            ConversationCreated::dispatch($conversation->id);
        }
        if ($message->isInbound()) {
            if ($fireInboundEvent) {
                // Positional — Dispatchable::dispatch là variadic; truyền named arg
                // qua spread không bind được (Unknown named parameter).
                MessageReceived::dispatch($message->id, $conversation->id, false);
            }

            // Relay media inbound (URL sàn TTL ngắn) vào object storage — chỉ
            // attachment chưa có storage_path (status pending). SPEC-0024 §6.4.
            // Luôn chạy bất kể $fireInboundEvent để không mất media backlog.
            if ($message->attachments_count > 0) {
                $message->attachments()
                    ->withoutGlobalScope(TenantScope::class)
                    ->where('status', MessageAttachment::STATUS_PENDING)
                    ->get()
                    ->each(fn (MessageAttachment $a) => DownloadInboundMedia::dispatch($a->id));
            }
        }
    }

    private function ensureConversation(ChannelAccount $channelAccount, MessageDTO $dto): Conversation
    {
        // Lookup with lock — chống race khi 2 webhook cùng tới về cùng conv mới.
        // withoutGlobalScope(TenantScope): không có tenant context trong job.
        $existing = Conversation::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $channelAccount->id)
            ->where('external_conversation_id', $dto->externalConversationId)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return Conversation::create([
                'tenant_id' => $channelAccount->tenant_id,
                'channel_account_id' => $channelAccount->id,
                // Conversation lưu MÃ messaging connector (shopee_chat/tiktok_chat/...), không phải
                // provider gian hàng (shopee/tiktok). Facebook/manual map về chính nó.
                'provider' => $channelAccount->messagingConnectorCode() ?? $channelAccount->provider,
                'external_conversation_id' => $dto->externalConversationId,
                'buyer_external_id' => $dto->buyerExternalId,
                'buyer_name' => null,
                'buyer_avatar_url' => null,
                'status' => Conversation::STATUS_OPEN,
                'unread_count' => 0,
                'message_count' => 0,
                'last_message_at' => $dto->sentAt ?? now(),
            ]);
        } catch (QueryException $e) {
            // Race: insert đồng thời với connection khác — re-lookup.
            $row = Conversation::withoutGlobalScope(TenantScope::class)
                ->where('channel_account_id', $channelAccount->id)
                ->where('external_conversation_id', $dto->externalConversationId)
                ->first();

            if ($row) {
                return $row;
            }

            throw $e;
        }
    }

    private function updateConversationOnNewMessage(Conversation $conversation, Message $message): void
    {
        $preview = $message->body !== null
            ? Str::limit(preg_replace('/\s+/', ' ', $message->body), 197)
            : '['.$message->kind.']';

        $conversation->message_count++;
        $conversation->last_message_at = $message->created_at;
        $conversation->last_message_preview = $preview;

        if ($message->isInbound()) {
            if ($conversation->blocked_at === null) {
                $conversation->unread_count++;
            }
            $conversation->last_inbound_at = $message->created_at;
            // Tin mới đẩy snoozed/resolved về open — nhưng KHÔNG bỏ chặn / không nổi nếu blocked.
            if ($conversation->blocked_at === null
                && in_array($conversation->status, [Conversation::STATUS_SNOOZED, Conversation::STATUS_RESOLVED], true)) {
                $conversation->status = Conversation::STATUS_OPEN;
                $conversation->snoozed_until = null;
            }
        } else {
            $conversation->last_outbound_at = $message->created_at;
        }

        $conversation->save();
    }
}
