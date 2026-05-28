<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\CommentReceived;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * CommentReceived → tìm flow comment khớp (comment_on_post → comment_any) và start.
 */
class StartFlowOnComment implements ShouldQueue
{
    public string $queue = 'messaging';

    public function __construct(private FlowEngine $engine, private FlowMatcher $matcher) {}

    public function handle(CommentReceived $event): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->thread_type !== Conversation::THREAD_COMMENT) {
            return;
        }
        $body = (string) (Message::withoutGlobalScope(TenantScope::class)->whereKey($event->messageId)->value('body') ?? '');

        $flows = $this->matcher->matching($conv, [
            AutomationFlow::TRIGGER_COMMENT_ON_POST,
            AutomationFlow::TRIGGER_COMMENT_ANY,
        ], $body);
        if ($flow = $flows->first()) {
            $this->engine->start($flow, $conv, $body);
        }
    }
}
