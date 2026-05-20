<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Trên mỗi inbound message: thử fire `first_message` (chào lần đầu), nếu không
 * có thì thử `schedule` (away-hours). Fire 1 cái (first-wins) để tránh khách
 * nhận 2 auto-reply cùng lúc trên 1 tin.
 *
 * Chỉ chạy trên INBOUND ⇒ auto-reply outbound không tự kích lại (chống loop, §4.3).
 * Bỏ qua conversation `spam`.
 */
class RunAutoReplyOnInbound
{
    public function __construct(private AutoReplyEngine $engine) {}

    public function handle(MessageReceived $event): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->status === Conversation::STATUS_SPAM) {
            return;
        }

        $message = Message::withoutGlobalScope(TenantScope::class)->find($event->messageId);
        if (! $message || ! $message->isInbound()) {
            return;
        }

        $context = ['inbound_body' => $message->body];

        $fired = $this->engine->fire($conv, \CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule::TRIGGER_FIRST_MESSAGE, $context);
        if ($fired === null) {
            $this->engine->fire($conv, \CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule::TRIGGER_SCHEDULE, $context);
        }
    }
}
