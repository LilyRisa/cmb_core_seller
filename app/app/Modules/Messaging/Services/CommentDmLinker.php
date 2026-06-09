<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\CommentDmLink;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Liên kết comment → DM theo bài viết (SPEC 2026-06-09): cho phép flow inbox lọc
 * theo bài viết nguồn và luồng comment→DM nhiều bước.
 *
 * - record(): gọi NGAY sau khi gửi tin riêng cho 1 comment (đã biết PSID người nhận
 *   + fb_post_id của comment) → upsert map (page, psid) → post.
 * - stampInbound(): khi có DM inbound đầu tiên, gắn fb_post_id vào hội thoại DM
 *   (first-touch, không ghi đè) để FlowMatcher khớp đúng bài viết.
 *
 * Chỉ áp dụng provider `facebook_page`. Chạy trong webhook/job ⇒ withoutGlobalScope.
 */
class CommentDmLinker
{
    private const FB_PROVIDER = 'facebook_page';

    /** Ghi/cập nhật map (page, psid) → bài viết. Mới nhất thắng. */
    public function record(int $tenantId, int $channelAccountId, string $psid, string $fbPostId, ?string $fbCommentId = null): void
    {
        if ($psid === '' || $fbPostId === '') {
            return;
        }

        CommentDmLink::withoutGlobalScope(TenantScope::class)->updateOrCreate(
            ['channel_account_id' => $channelAccountId, 'psid' => $psid],
            ['tenant_id' => $tenantId, 'fb_post_id' => $fbPostId, 'fb_comment_id' => $fbCommentId, 'linked_at' => now()],
        );
    }

    /**
     * Gắn bài viết nguồn cho hội thoại DM khi có tin inbound (first-touch). An toàn
     * gọi cho mọi inbound: early-return nếu không phải Facebook / đã có post / không có link.
     */
    public function stampInbound(ChannelAccount $account, Conversation $conv, MessageDTO $dto): void
    {
        if ($account->provider !== self::FB_PROVIDER || $dto->direction !== MessageDirection::Inbound) {
            return;
        }
        if ($conv->thread_type !== Conversation::THREAD_MESSAGE) {
            return; // chỉ hội thoại DM
        }
        $meta = (array) ($conv->meta ?? []);
        if (($meta['fb_post_id'] ?? '') !== '') {
            return; // first-touch: giữ bài viết đã gắn của hội thoại đang chạy
        }
        $psid = (string) $conv->buyer_external_id;
        if ($psid === '') {
            return;
        }

        $link = CommentDmLink::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $account->id)
            ->where('psid', $psid)
            ->first();
        if (! $link) {
            return;
        }

        $meta['fb_post_id'] = $link->fb_post_id;
        if ($link->fb_comment_id !== null && ($meta['fb_comment_id'] ?? '') === '') {
            $meta['fb_comment_id'] = $link->fb_comment_id;
        }
        $meta['fb_post_source'] = 'comment_dm_link';
        $conv->forceFill(['meta' => $meta])->save();
    }
}
