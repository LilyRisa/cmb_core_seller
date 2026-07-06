<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Lấy avatar tác giả 1 comment (path WEBHOOK realtime) rồi relay + lưu vào:
 *  1. `messages.meta.author_avatar_path/url` của ĐÚNG tin comment đó — avatar hiển thị
 *     theo TỪNG bình luận trong đoạn chat (thread nhiều người).
 *  2. `conversation.meta.comment_participant_avatars` (cap 2) — FE chồng 2 avatar ngoài
 *     danh sách + header.
 *
 * Feed webhook chỉ có from{id,name} (không kèm ảnh) ⇒ phải gọi Graph
 * `{comment_id}?fields=from{picture}`. Backfill (`fetchCommentThreads`) đã có sẵn
 * ảnh nên tự relay vào message.meta; đây chỉ bù cho comment MỚI về realtime.
 *
 * Best-effort: tin đã có avatar & stack đủ 2 / thiếu quyền / lỗi ⇒ thoát êm
 * (FE fallback chữ cái đầu).
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

    public function handle(MessagingRegistry $registry, CommentConversationUpserter $upserter, MessagingAvatarRelay $relay): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($this->conversationId);

        if (! $conv || $conv->thread_type !== Conversation::THREAD_COMMENT) {
            return;
        }

        // Tin comment tương ứng (external_message_id == id comment vừa về).
        $message = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('external_message_id', $this->commentId)
            ->first();

        // Tin đã có avatar tác giả (relay xong lần trước) VÀ stack đã đủ 2 ⇒ khỏi gọi Graph.
        $msgHasAvatar = $message !== null && filled(($message->meta ?? [])['author_avatar_path'] ?? null);
        $stored = is_array(($conv->meta ?? [])['comment_participant_avatars'] ?? null)
            ? ($conv->meta['comment_participant_avatars'] ?? [])
            : [];
        if ($msgHasAvatar && count($stored) >= 2) {
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
        if (! filled($author['avatar_url'])) {
            return;
        }
        $avatarUrl = (string) $author['avatar_url'];

        // Stack ngoài danh sách/header (cap 2, dedupe theo tên).
        if (count($stored) < 2) {
            $upserter->storeParticipantAvatars($conv, [[
                'name' => $author['name'] ?? null,
                'url' => $avatarUrl,
            ]]);
        }

        // Avatar theo TỪNG tin — relay về storage (bền) rồi ghi vào message.meta.
        if ($message !== null && ! $msgHasAvatar) {
            $path = $relay->relay((int) $conv->tenant_id, $avatarUrl);
            $meta = is_array($message->meta) ? $message->meta : [];
            $meta['author_avatar_url'] = $avatarUrl;
            if (filled($author['name'] ?? null) && empty($meta['author_name'])) {
                $meta['author_name'] = (string) $author['name'];
            }
            if ($path !== null) {
                $meta['author_avatar_path'] = $path;
            }
            $message->meta = $meta;
            $message->save();
        }
    }
}
