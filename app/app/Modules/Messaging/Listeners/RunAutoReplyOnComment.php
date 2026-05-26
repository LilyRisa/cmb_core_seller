<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\CommentReceived;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Trên mỗi comment inbound mới: thử fire `first_message` (chào bình luận đầu của
 * người đó) → `keyword` (bình luận chứa từ khoá) → `comment_any` (mọi bình luận).
 * Fire 1 cái (first-wins) tránh trả lời trùng trên 1 comment.
 *
 * CHỈ áp dụng rule khai báo tường minh `filter.thread_types` chứa `comment`
 * (đảm bảo ở AutoReplyEngine::matchesFilter) — rule DM cũ không vô tình đăng công khai.
 *
 * ShouldQueue (queue `messaging`): action `ai_reply` gọi LLM tốn thời gian —
 * không chặn webhook ingest.
 */
class RunAutoReplyOnComment implements ShouldQueue
{
    public string $queue = 'messaging';

    public function __construct(private AutoReplyEngine $engine) {}

    public function handle(CommentReceived $event): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->thread_type !== Conversation::THREAD_COMMENT) {
            return;
        }
        if ($conv->status === Conversation::STATUS_SPAM || $conv->blocked_at !== null) {
            return;
        }

        $message = Message::withoutGlobalScope(TenantScope::class)->find($event->messageId);
        if (! $message || ! $message->isInbound()) {
            return;
        }

        $context = [
            'inbound_body' => $message->body,
            'external_message_id' => $message->external_message_id,
            'message_id' => $message->id,
        ];

        $fired = $this->engine->fire($conv, AutoReplyRule::TRIGGER_FIRST_MESSAGE, $context);
        if ($fired === null) {
            $fired = $this->engine->fire($conv, AutoReplyRule::TRIGGER_KEYWORD, $context);
        }
        if ($fired === null) {
            $this->engine->fire($conv, AutoReplyRule::TRIGGER_COMMENT_ANY, $context);
        }
    }
}
