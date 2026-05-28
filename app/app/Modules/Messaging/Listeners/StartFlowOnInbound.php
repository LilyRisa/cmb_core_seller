<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * MessageReceived (DM) → nếu có run đang `waiting` ⇒ resume; ngược lại tìm flow
 * khớp (first_message → keyword → any) và start. Chạy SONG SONG auto-reply phẳng.
 */
class StartFlowOnInbound implements ShouldQueue
{
    public string $queue = 'messaging';

    public function __construct(private FlowEngine $engine, private FlowMatcher $matcher) {}

    public function handle(MessageReceived $event): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->thread_type !== Conversation::THREAD_MESSAGE) {
            return;
        }
        $body = (string) (Message::withoutGlobalScope(TenantScope::class)->whereKey($event->messageId)->value('body') ?? '');

        $waiting = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->where('status', FlowRun::STATUS_WAITING)->first();
        if ($waiting) {
            $this->engine->resume($waiting, $conv, $body);

            return;
        }

        $flows = $this->matcher->matching($conv, [
            AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            AutomationFlow::TRIGGER_INBOX_KEYWORD,
            AutomationFlow::TRIGGER_INBOX_ANY,
        ], $body);
        if ($flow = $flows->first()) {
            $this->engine->start($flow, $conv, $body);
        }
    }
}
