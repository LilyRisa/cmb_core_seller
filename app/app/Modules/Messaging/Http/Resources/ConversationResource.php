<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Resources;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /** Resolve MediaStorage một lần cho cả list (tránh app() resolve per-row). */
    private static ?MediaStorage $mediaStorage = null;

    private static function mediaStorage(): MediaStorage
    {
        return self::$mediaStorage ??= app(MediaStorage::class);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_account_id' => $this->channel_account_id,
            'provider' => $this->provider,
            'thread_type' => $this->thread_type ?? 'message',
            'comment' => $this->thread_type === 'comment' ? [
                'post_message' => $this->meta['fb_post_message'] ?? null,
                'post_permalink' => $this->meta['fb_post_permalink'] ?? null,
                // Ảnh bài viết (CDN hết hạn — chỉ preview) + giờ đăng, cho post card hộp thư.
                'post_picture' => $this->meta['fb_post_picture'] ?? null,
                'post_created_time' => ! empty($this->meta['fb_post_created_time'])
                    ? CarbonImmutable::parse((string) $this->meta['fb_post_created_time'])->toIso8601String()
                    : null,
                'hidden' => (bool) ($this->meta['comment_hidden'] ?? false),
                'private_replied' => ! empty($this->meta['private_replied_at']),
                // Người tham gia comment (commenter + người reply) — FE hiển thị "A, B +N người".
                'participants' => array_values(array_filter(
                    (array) ($this->meta['comment_participants'] ?? []),
                    fn ($n) => is_string($n) && $n !== '',
                )),
            ] : null,
            // Nguồn gốc hội thoại: nhóm kênh (marketplace/facebook/internal) + tên
            // shop/page cụ thể — FE tách "tin nhắn sàn" vs "tin nhắn Facebook" + hiện
            // rõ tin đến từ đâu (SPEC-0024 §3.1).
            'channel_group' => self::groupFor((string) $this->provider),
            'channel_account_name' => $this->channelAccount?->effectiveName(),
            // Avatar page/gian hàng — hiện cho tin OUTBOUND (giống app nhắn tin chuẩn).
            // Ưu tiên signed URL storage (đã relay), fallback URL CDN sàn.
            'channel_account_avatar_url' => $this->whenLoaded('pageMeta', fn () => $this->pageMeta
                ? (self::mediaStorage()->temporaryUrlForPath($this->pageMeta->page_avatar_path) ?? $this->pageMeta->page_avatar_url)
                : null),
            'external_conversation_id' => $this->external_conversation_id,
            'buyer_external_id' => $this->buyer_external_id,
            'buyer_name' => $this->buyer_name,
            // Ưu tiên signed URL từ object storage (đã relay); fallback URL CDN sàn
            // (`buyer_avatar_url`) khi chưa relay / storage chưa cấu hình (vd R2 thiếu
            // env ở prod) — tránh mất avatar. temporaryUrlForPath(null) trả null ⇒ fallback.
            'buyer_avatar_url' => self::mediaStorage()->temporaryUrlForPath($this->buyer_avatar_path)
                ?? $this->buyer_avatar_url,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'snoozed_until' => $this->snoozed_until?->toIso8601String(),
            'unread_count' => (int) $this->unread_count,
            'message_count' => (int) $this->message_count,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_message_preview' => $this->last_message_preview,
            'last_inbound_at' => $this->last_inbound_at?->toIso8601String(),
            'last_outbound_at' => $this->last_outbound_at?->toIso8601String(),
            'assigned_user_id' => $this->assigned_user_id,
            'tags' => $this->tags ?? [],
            'has_phone' => (bool) $this->has_phone,
            'detected_phone' => $this->detected_phone,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** Nhóm nguồn để FE tách inbox: sàn TMĐT vs Facebook vs nội bộ. */
    public static function groupFor(string $provider): string
    {
        return match ($provider) {
            'facebook_page' => 'facebook',
            'tiktok_chat', 'shopee_chat', 'lazada_chat' => 'marketplace',
            default => 'internal',
        };
    }
}
