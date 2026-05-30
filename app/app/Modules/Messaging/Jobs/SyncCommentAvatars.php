<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Lấy avatar tác giả 1 comment (path WEBHOOK realtime) rồi relay + lưu vào
 * `meta.comment_participant_avatars` để FE chồng 2 avatar comment thread.
 *
 * Feed webhook chỉ có from{id,name} (không kèm ảnh) ⇒ phải gọi Graph
 * `{comment_id}?fields=from{picture}`. Backfill (`fetchCommentThreads`) đã có sẵn
 * ảnh nên không cần job này; đây chỉ bù cho comment MỚI về realtime.
 *
 * Best-effort: đã đủ 2 avatar / thiếu quyền / lỗi ⇒ thoát êm (FE fallback chữ cái đầu).
 */
class SyncCommentAvatars implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $conversationId, public string $commentId)
    {
        $this->onQueue('messaging-sync');
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(MessagingRegistry $registry, CommentConversationUpserter $upserter): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($this->conversationId);

        if (! $conv || $conv->thread_type !== Conversation::THREAD_COMMENT) {
            return;
        }

        // Đã đủ 2 avatar cho stack ⇒ không cần gọi Graph nữa.
        $stored = is_array(($conv->meta ?? [])['comment_participant_avatars'] ?? null)
            ? ($conv->meta['comment_participant_avatars'] ?? [])
            : [];
        if (count($stored) >= 2) {
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

        $author = $connector->fetchCommentAuthorAvatar($auth, $this->commentId);
        if (filled($author['avatar_url'])) {
            $upserter->storeParticipantAvatars($conv, [[
                'name' => $author['name'] ?? null,
                'url' => (string) $author['avatar_url'],
            ]]);
        }
    }
}
