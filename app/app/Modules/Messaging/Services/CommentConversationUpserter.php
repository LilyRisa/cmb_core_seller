<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Upsert a comment-thread conversation for Facebook Page.
 *
 * Extracted from BackfillFacebookComments so both the backfill job and the real-time
 * webhook processor (ProcessMessagingWebhook) share the same idempotent upsert logic.
 *
 * Contract:
 *   - Looks up `conversations` by (channel_account_id, external_conversation_id).
 *   - Creates a new row with thread_type='comment' if not found.
 *   - Always refreshes thread_type, buyer_name (if provided), and meta fields
 *     (fb_post_id, fb_comment_id — merges onto existing meta so callers can pass a
 *     subset and not clobber unrelated meta keys).
 *
 * Context array keys (all optional):
 *   - top_level_comment_id (string)  — the conversation id / top-level comment id
 *   - buyer_external_id  (string)
 *   - buyer_name         (?string)
 *   - fb_post_id         (?string)
 *   - fb_comment_id      (?string)
 *   - occurred_at        (?CarbonImmutable)
 */
class CommentConversationUpserter
{
    /** Tối đa avatar người tham gia lưu cho stack (FE chồng 2 avatar). */
    private const MAX_PARTICIPANT_AVATARS = 2;

    public function __construct(private MessagingAvatarRelay $avatarRelay) {}

    /**
     * @param  array<string, mixed>  $ctx
     */
    public function upsert(ChannelAccount $account, array $ctx): Conversation
    {
        $externalConvId = (string) ($ctx['top_level_comment_id'] ?? $ctx['fb_comment_id'] ?? '');
        $buyerExternalId = (string) ($ctx['buyer_external_id'] ?? '');
        $buyerName = isset($ctx['buyer_name']) && (string) $ctx['buyer_name'] !== ''
            ? (string) $ctx['buyer_name']
            : null;
        /** @var CarbonImmutable|null $occurredAt */
        $occurredAt = $ctx['occurred_at'] ?? null;

        $conv = Conversation::withoutGlobalScope(TenantScope::class)->firstOrNew([
            'channel_account_id' => (int) $account->getKey(),
            'external_conversation_id' => $externalConvId,
        ]);

        if (! $conv->exists) {
            $conv->tenant_id = (int) $account->tenant_id;
            $conv->provider = $account->messagingConnectorCode() ?? $account->provider;
            $conv->buyer_external_id = $buyerExternalId;
            $conv->status = Conversation::STATUS_OPEN;
            $conv->unread_count = 0;
            $conv->message_count = 0;
            $conv->last_message_at = $occurredAt ?? now();
        }

        $conv->thread_type = Conversation::THREAD_COMMENT;

        if ($buyerName !== null) {
            $conv->buyer_name = $buyerName;
        }

        $meta = (array) ($conv->meta ?? []);

        $metaUpdates = array_filter([
            'fb_post_id' => isset($ctx['fb_post_id']) ? (string) $ctx['fb_post_id'] : null,
            'fb_comment_id' => isset($ctx['fb_comment_id']) ? (string) $ctx['fb_comment_id'] : null,
        ], fn ($v) => $v !== null);

        if ($metaUpdates !== []) {
            $meta = array_merge($meta, $metaUpdates);
        }

        // Gộp tên người tham gia comment (commenter + người reply) — distinct, giữ thứ tự
        // (commenter trước), cap 20. FE hiển thị "A, B +N người". Tên page bị loại ở caller.
        $incoming = array_values(array_filter(
            (array) ($ctx['participant_names'] ?? []),
            fn ($n) => is_string($n) && trim($n) !== '',
        ));
        if ($incoming !== []) {
            $participants = is_array($meta['comment_participants'] ?? null) ? $meta['comment_participants'] : [];
            foreach ($incoming as $name) {
                $name = trim((string) $name);
                if (! in_array($name, $participants, true) && count($participants) < 20) {
                    $participants[] = $name;
                }
            }
            $meta['comment_participants'] = $participants;
        }

        $conv->meta = $meta;
        $conv->save();

        // Avatar người tham gia (commenter + người reply, trừ page) — relay về storage
        // rồi lưu để FE chồng 2 avatar. Backfill cung cấp URL; webhook đi job riêng.
        if (! empty($ctx['participant_avatars']) && is_array($ctx['participant_avatars'])) {
            $this->storeParticipantAvatars($conv, $ctx['participant_avatars']);
        }

        return $conv;
    }

    /**
     * Relay (về object storage) + lưu avatar người tham gia comment vào
     * `meta.comment_participant_avatars` (list `{name, path, url}`), dedupe theo tên,
     * cap 2. Idempotent: đã đủ 2 ⇒ bỏ qua; URL CDN hết hạn nên relay để giữ ảnh.
     *
     * @param  list<array{name?: ?string, url?: ?string}>  $pairs
     */
    public function storeParticipantAvatars(Conversation $conv, array $pairs): void
    {
        $meta = (array) ($conv->meta ?? []);
        $stored = is_array($meta['comment_participant_avatars'] ?? null)
            ? array_values($meta['comment_participant_avatars'])
            : [];

        $existingNames = array_filter(array_map(fn ($a) => is_array($a) ? ($a['name'] ?? null) : null, $stored));
        $changed = false;

        foreach ($pairs as $pair) {
            if (count($stored) >= self::MAX_PARTICIPANT_AVATARS) {
                break;
            }
            $url = isset($pair['url']) ? trim((string) $pair['url']) : '';
            if ($url === '') {
                continue;
            }
            $name = isset($pair['name']) ? trim((string) $pair['name']) : '';
            // Dedupe theo tên (khi có tên) — tránh trùng cùng 1 người.
            if ($name !== '' && in_array($name, $existingNames, true)) {
                continue;
            }

            $path = $this->avatarRelay->relay((int) $conv->tenant_id, $url);
            $stored[] = array_filter([
                'name' => $name !== '' ? $name : null,
                'path' => $path,           // null nếu relay lỗi ⇒ FE fallback `url`
                'url' => $url,             // URL CDN gốc (fallback hiển thị)
            ], fn ($v) => $v !== null);
            if ($name !== '') {
                $existingNames[] = $name;
            }
            $changed = true;
        }

        if ($changed) {
            $meta['comment_participant_avatars'] = $stored;
            $conv->meta = $meta;
            $conv->save();
        }
    }
}
