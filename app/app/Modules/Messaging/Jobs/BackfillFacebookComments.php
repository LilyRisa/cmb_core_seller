<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Backfill comment threads từ page feed Facebook vào messaging inbox.
 *
 * Mỗi top-level comment của khách hàng = 1 `conversations` row riêng (thread_type='comment').
 * Reply của page = outbound message; reply của khách = inbound message.
 *
 * Idempotent: ingest dedupe theo (channel_account_id, external_conversation_id) ở conversation
 * và (conversation_id, external_message_id) ở message.
 *
 * KHÔNG fire auto-reply events (fireEventsForNewMessage) — backfill tin cũ.
 * KHÔNG relay media (comments là text; bỏ qua DownloadInboundMedia).
 *
 * Sử dụng comment_sync_status / comment_synced_at / comment_sync_error riêng — KHÔNG
 * đụng vào message sync_status để tránh làm bẩn trạng thái BackfillMessagingChannel.
 *
 * Constructor: `(int $channelAccountId, ?string $sinceIso = null)`.
 */
class BackfillFacebookComments implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $uniqueFor = 900;

    public function __construct(public int $channelAccountId, public ?string $sinceIso = null)
    {
        $this->onQueue('messaging-sync');
    }

    public function uniqueId(): string
    {
        return "comments:{$this->channelAccountId}";
    }

    public function handle(MessagingRegistry $registry, MessageIngestionService $ingestion): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);

        $code = $account?->messagingConnectorCode();
        if (! $account || $code === null || ! $registry->has($code)) {
            return;
        }
        /** @var FacebookPageConnector $connector */
        $connector = $registry->for($code);
        if (! $connector->supports('inbound.comments')) {
            return;
        }

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['channel_account_id' => (int) $account->getKey()],
            ['tenant_id' => (int) $account->tenant_id, 'messaging_enabled' => true],
        );

        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->getKey(),
            provider: $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) $account->access_token,
        );

        $meta->forceFill([
            'comment_sync_status' => MessagingAccountMeta::SYNC_RUNNING,
            'comment_sync_error' => null,
        ])->save();

        $cutoff = $this->sinceIso
            ? Carbon::parse($this->sinceIso)
            : now()->subDays((int) config('messaging.backfill.days', 90));

        $cursor = null;

        try {
            do {
                $result = $connector->fetchCommentThreads($auth, [
                    'pageSize' => (int) config('messaging.backfill.posts_per_page', 10),
                    'commentLimit' => (int) config('messaging.backfill.comments_per_post', 50),
                    'cursor' => $cursor,
                ]);

                foreach ($result['items'] as $thread) {
                    $createdTime = ! empty($thread['created_time'])
                        ? CarbonImmutable::parse((string) $thread['created_time'])
                        : null;

                    // Cutoff check — stop pagination when comment is older than cutoff
                    if ($createdTime !== null && $createdTime->lt($cutoff)) {
                        // Items are roughly newest-first; once we see an old one, stop
                        break 2;
                    }

                    $this->ingestThread($account, $auth, $ingestion, $thread);
                }

                $prevCursor = $cursor;
                $cursor = $result['nextCursor'];

                // Guard against infinite loop on stuck cursor
                if ($cursor !== null && $cursor === $prevCursor) {
                    break;
                }
            } while ($result['hasMore'] && $cursor !== null);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'FACEBOOK_RATE_LIMIT')) {
                $meta->forceFill(['comment_sync_status' => MessagingAccountMeta::SYNC_QUEUED])->save();
                $this->release(120);

                return;
            }

            $msg = $e->getMessage();
            if (
                str_contains($msg, 'pages_read_engagement') ||
                str_contains($msg, 'Page Public Content Access') ||
                str_contains($msg, '(#10)') ||
                (str_contains($msg, 'OAuthException') && preg_match('/"code"\s*:\s*(?:10|200)\b/', $msg))
            ) {
                $meta->forceFill([
                    'comment_sync_status' => MessagingAccountMeta::SYNC_FAILED,
                    'comment_sync_error' => 'Thiếu quyền đọc nội dung trang (pages_read_engagement). Hãy kết nối lại page để cấp quyền đồng bộ comment.',
                ])->save();
                Log::warning('messaging.comments_backfill.permission_denied', ['account' => $account->id, 'error' => $msg]);

                return; // permanent error — do not retry
            }

            $meta->forceFill([
                'comment_sync_status' => MessagingAccountMeta::SYNC_FAILED,
                'comment_sync_error' => substr($msg, 0, 250),
            ])->save();
            Log::warning('messaging.comments_backfill.failed', ['account' => $account->id, 'error' => $msg]);

            return;
        }

        $meta->forceFill([
            'comment_sync_status' => MessagingAccountMeta::SYNC_DONE,
            'comment_synced_at' => now(),
            'comment_sync_error' => null,
        ])->save();
    }

    /**
     * Terminal-state hook — when all tries exhausted, land row in SYNC_FAILED.
     */
    public function failed(\Throwable $e): void
    {
        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if ($meta && in_array($meta->comment_sync_status, [MessagingAccountMeta::SYNC_RUNNING, MessagingAccountMeta::SYNC_QUEUED], true)) {
            $meta->forceFill([
                'comment_sync_status' => MessagingAccountMeta::SYNC_FAILED,
                'comment_sync_error' => substr($e->getMessage(), 0, 250),
            ])->save();
        }
    }

    /**
     * Upsert conversation + ingest comment + replies for 1 top-level comment thread.
     *
     * @param  array<string,mixed>  $thread  Normalized shape from fetchCommentThreads
     */
    private function ingestThread(
        ChannelAccount $account,
        MessagingAuthContext $auth,
        MessageIngestionService $ingestion,
        array $thread,
    ): void {
        $commentId = (string) $thread['comment_id'];
        $commenterId = (string) $thread['commenter_id'];
        $commenterName = isset($thread['commenter_name']) ? (string) $thread['commenter_name'] : null;
        $message = isset($thread['message']) ? (string) $thread['message'] : null;
        $createdTime = ! empty($thread['created_time'])
            ? CarbonImmutable::parse((string) $thread['created_time'])
            : null;

        // Người tham gia comment = commenter + người reply (KHÔNG tính page). Tên dùng để
        // hiển thị "A, B +N người" ở comment thread.
        $participantNames = [];
        if ($commenterName !== null && $commenterName !== '') {
            $participantNames[] = $commenterName;
        }
        foreach ((array) ($thread['replies'] ?? []) as $reply) {
            if ((string) ($reply['from_id'] ?? '') === $auth->externalShopId) {
                continue; // reply của page — không phải người comment
            }
            $rName = isset($reply['from_name']) ? (string) $reply['from_name'] : '';
            if ($rName !== '') {
                $participantNames[] = $rName;
            }
        }

        // --- 1. Upsert conversation (comment thread) via shared upserter ---
        $upserter = app(CommentConversationUpserter::class);
        $conv = $upserter->upsert($account, [
            'top_level_comment_id' => $commentId,
            'buyer_external_id' => $commenterId,
            'buyer_name' => $commenterName,
            'participant_names' => $participantNames,
            'fb_post_id' => (string) $thread['post_id'],
            'fb_comment_id' => $commentId,
            'occurred_at' => $createdTime,
        ]);

        // Backfill also carries post_permalink + post_message in meta.
        $existingMeta = (array) ($conv->meta ?? []);
        $conv->meta = array_merge($existingMeta, [
            'fb_post_permalink' => $thread['post_permalink'],
            'fb_post_message' => $thread['post_message'],
        ]);
        $conv->save();

        // --- 2. Ingest top-level comment as inbound message ---
        $ingestion->ingest($account, new MessageDTO(
            externalConversationId: $commentId,
            externalMessageId: $commentId,
            buyerExternalId: $commenterId,
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: $message,
            sentAt: $createdTime,
            raw: [
                'type' => 'comment',
                'post_id' => $thread['post_id'],
                'comment_id' => $commentId,
            ],
        ));

        // --- 3. Ingest replies ---
        foreach ((array) ($thread['replies'] ?? []) as $reply) {
            $replyId = (string) ($reply['id'] ?? '');
            $replyFromId = (string) ($reply['from_id'] ?? '');
            if ($replyId === '') {
                continue;
            }

            $replyDirection = $replyFromId === $auth->externalShopId
                ? MessageDirection::Outbound
                : MessageDirection::Inbound;

            $replySentAt = ! empty($reply['created_time'])
                ? CarbonImmutable::parse((string) $reply['created_time'])
                : null;

            $ingestion->ingest($account, new MessageDTO(
                externalConversationId: $commentId,
                externalMessageId: $replyId,
                buyerExternalId: $commenterId,
                direction: $replyDirection,
                kind: MessageKind::Text,
                body: isset($reply['message']) ? (string) $reply['message'] : null,
                sentAt: $replySentAt,
                raw: [
                    'type' => 'reply',
                    'post_id' => $thread['post_id'],
                    'comment_id' => $commentId,
                    'reply_id' => $replyId,
                    'from_id' => $replyFromId,
                ],
            ));
        }
    }
}
