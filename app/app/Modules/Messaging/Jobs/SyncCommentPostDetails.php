<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Lấy chi tiết bài viết (nội dung/link/ảnh/giờ đăng) cho 1 hội thoại bình luận đến từ
 * WEBHOOK realtime rồi lưu vào `conversation.meta` để `CommentPostCard` hiển thị bài viết.
 *
 * Feed webhook chỉ kèm `fb_post_id` (KHÔNG có nội dung bài) ⇒ post card trống. Backfill
 * (`fetchCommentThreads`) đã có sẵn nội dung bài nên không cần job này; đây chỉ bù cho
 * comment MỚI về realtime.
 *
 * Best-effort + idempotent: đã có `fb_post_message`/`fb_post_permalink` ⇒ thoát êm (không
 * gọi Graph lại); Graph lỗi/thiếu quyền ⇒ giữ nguyên (post card ẩn phần thiếu).
 */
class SyncCommentPostDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $conversationId, public string $postId)
    {
        $this->onQueue('messaging-sync');
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(MessagingRegistry $registry): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($this->conversationId);

        if (! $conv || $conv->thread_type !== Conversation::THREAD_COMMENT) {
            return;
        }

        // Đã có nội dung/link bài ⇒ idempotent, khỏi gọi Graph. (Bài chỉ có ảnh vẫn có
        // permalink ⇒ guard vẫn trip sau lần fetch đầu.)
        $meta = is_array($conv->meta) ? $conv->meta : [];
        if (filled($meta['fb_post_message'] ?? null) || filled($meta['fb_post_permalink'] ?? null)) {
            return;
        }

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($conv->channel_account_id);
        $code = $account?->messagingConnectorCode();
        if (! $account || $code === null || ! $registry->has($code)) {
            return;
        }
        $connector = $registry->for($code);
        if (! $connector instanceof FacebookPageConnector) {
            return;
        }

        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->getKey(),
            provider: (string) $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) $account->access_token,
        );

        $post = $connector->fetchPostDetails($auth, $this->postId);

        // Không lấy được gì (lỗi/thiếu quyền) ⇒ giữ nguyên; tin comment sau sẽ thử lại.
        if (blank($post['message']) && blank($post['permalink']) && blank($post['picture'])) {
            return;
        }

        $meta['fb_post_message'] = $post['message'];
        $meta['fb_post_permalink'] = $post['permalink'];
        $meta['fb_post_picture'] = $post['picture'];
        $meta['fb_post_is_video'] = $post['is_video'];
        $meta['fb_post_created_time'] = $post['created_time'];
        $conv->forceFill(['meta' => $meta])->save();
    }
}
