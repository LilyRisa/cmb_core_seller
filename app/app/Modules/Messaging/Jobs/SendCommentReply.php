<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\Contracts\CommentEngagementConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\CommentDmLinker;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Gửi auto-reply cho 1 comment thread: trả lời công khai và/hoặc nhắn riêng cho
 * người bình luận, tuỳ `comment_target` của rule.
 *
 * Provider-agnostic: kiểm `comment.reply_public` / `comment.reply_private` qua
 * connector; thiếu capability ⇒ bỏ qua nhánh đó (không ném — không spam).
 *
 * `tries=1` CỐ Ý: comment reply công khai KHÔNG idempotent (mỗi call tạo 1
 * sub-comment); retry sẽ đăng trùng. Idempotency thực nằm ở engine
 * (`auto_reply_runs` unique window) đã chặn dispatch lặp. Lỗi gửi ⇒ log, không retry.
 */
class SendCommentReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $conversationId,
        public string $body,
        public bool $public = true,
        public bool $private = false,
        public ?int $autoRuleId = null,
        public ?int $aiRunId = null,
    ) {
        $this->onQueue('messaging-outbound');
    }

    public function handle(MessagingRegistry $registry, MessageIngestionService $ingestion, CommentDmLinker $linker): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($this->conversationId);
        if (! $conv || $conv->thread_type !== Conversation::THREAD_COMMENT) {
            return;
        }
        if ($conv->status === Conversation::STATUS_SPAM || $conv->blocked_at !== null) {
            return;
        }

        $commentId = (string) ($conv->meta['fb_comment_id'] ?? '');
        if ($commentId === '' || ! $registry->has($conv->provider)) {
            return;
        }

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($conv->channel_account_id);
        if (! $account || $account->status !== ChannelAccount::STATUS_ACTIVE) {
            return;
        }

        $connector = $registry->for($conv->provider);
        $auth = new MessagingAuthContext(
            channelAccountId: $account->id,
            provider: $conv->provider,
            externalShopId: $account->external_shop_id,
            accessToken: (string) ($account->access_token ?? ''),
            extra: (array) ($account->meta ?? []),
        );

        if ($this->public && $connector->supports('comment.reply_public')) {
            try {
                $newCommentId = $connector->replyToComment($auth, $commentId, $this->body);
                $ingestion->ingest($account, new MessageDTO(
                    externalConversationId: $conv->external_conversation_id,
                    externalMessageId: $newCommentId,
                    buyerExternalId: $conv->buyer_external_id,
                    direction: MessageDirection::Outbound,
                    kind: MessageKind::Text,
                    body: $this->body,
                    sentAt: now()->toImmutable(),
                    meta: array_filter([
                        'auto_rule_id' => $this->autoRuleId,
                        'ai_run_id' => $this->aiRunId,
                        'auto_comment_reply' => true,
                    ]),
                ));
            } catch (\Throwable $e) {
                Log::warning('messaging.comment.auto_reply_public.failed', [
                    'conversation_id' => $conv->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->private && $connector->supports('comment.reply_private')) {
            try {
                // Dùng sendCommentPrivateMessage (lấy PSID người nhận) thay privateReplyToComment
                // (void) để liên kết comment→DM theo bài viết (SPEC 2026-06-09). Connector cũ
                // không hỗ trợ ⇒ fallback bản void, vẫn gửi bình thường.
                $psid = '';
                if ($connector instanceof CommentEngagementConnector) {
                    $result = $connector->sendCommentPrivateMessage($auth, $commentId, null, $this->body);
                    $psid = (string) $result['psid'];
                } else {
                    $connector->privateReplyToComment($auth, $commentId, $this->body);
                }

                $meta = (array) ($conv->meta ?? []);
                $meta['private_replied_at'] = now()->toIso8601String();
                if ($psid !== '') {
                    $meta['fb_private_psid'] = $psid;
                }
                $conv->forceFill(['meta' => $meta])->save();

                // Map (page, psid) → bài viết: khi khách trả lời trong Messenger, hội thoại DM
                // được gắn bài viết nguồn ⇒ flow inbox theo bài viết / funnel comment→DM khớp đúng.
                $linker->record(
                    (int) $conv->tenant_id,
                    (int) $conv->channel_account_id,
                    $psid,
                    (string) (($conv->meta ?? [])['fb_post_id'] ?? ''),
                    $commentId,
                );
            } catch (\Throwable $e) {
                Log::warning('messaging.comment.auto_reply_private.failed', [
                    'conversation_id' => $conv->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
