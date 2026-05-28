<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Events\CommentReceived;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Listeners\StartFlowOnComment;
use CMBcoreSeller\Modules\Messaging\Listeners\StartFlowOnInbound;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FlowListenersTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_first_message_starts_matching_flow(): void
    {
        Queue::fake();

        $conv = Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 1,
        ]);
        $msg = Message::create([
            'tenant_id' => 1, 'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT, 'body' => 'xin chào', 'delivery_status' => Message::STATUS_SENT,
        ]);
        AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'Greeting', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            'enabled' => true,
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 'g', 'type' => 'send_message', 'data' => ['text' => 'Chào bạn!']],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [
                    ['source' => 't', 'target' => 'g', 'sourceHandle' => null],
                    ['source' => 'g', 'target' => 'e', 'sourceHandle' => null],
                ],
            ],
        ]);

        (new StartFlowOnInbound(app(FlowEngine::class), app(FlowMatcher::class)))
            ->handle(new MessageReceived($msg->id, $conv->id));

        $this->assertSame(1, FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->where('status', FlowRun::STATUS_COMPLETED)->count());
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->where('body', 'Chào bạn!')->count());
    }

    public function test_inbound_ignores_comment_thread(): void
    {
        Queue::fake();

        $conv = Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_COMMENT, 'external_conversation_id' => 'cm1',
            'buyer_external_id' => 'b2', 'status' => 'open', 'message_count' => 1,
        ]);
        $msg = Message::create([
            'tenant_id' => 1, 'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT, 'body' => 'hello', 'delivery_status' => Message::STATUS_SENT,
        ]);
        AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'Any', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'enabled' => true,
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [['source' => 't', 'target' => 'e', 'sourceHandle' => null]],
            ],
        ]);

        (new StartFlowOnInbound(app(FlowEngine::class), app(FlowMatcher::class)))
            ->handle(new MessageReceived($msg->id, $conv->id));

        $this->assertSame(0, FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->count());
    }

    public function test_inbound_resumes_waiting_run(): void
    {
        Queue::fake();

        $conv = Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c2',
            'buyer_external_id' => 'b3', 'status' => 'open', 'message_count' => 2,
        ]);
        $msg = Message::create([
            'tenant_id' => 1, 'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT, 'body' => 'có', 'delivery_status' => Message::STATUS_SENT,
        ]);
        $flow = AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'Wait', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'enabled' => true,
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 'w', 'type' => 'wait_reply', 'data' => []],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [
                    ['source' => 't', 'target' => 'w', 'sourceHandle' => null],
                    ['source' => 'w', 'target' => 'e', 'sourceHandle' => null],
                ],
            ],
        ]);
        // Pre-create a waiting run at the wait_reply node
        $run = FlowRun::create([
            'tenant_id' => 1, 'flow_id' => $flow->id, 'conversation_id' => $conv->id,
            'status' => FlowRun::STATUS_WAITING, 'current_node_id' => 'w', 'context' => [],
        ]);

        (new StartFlowOnInbound(app(FlowEngine::class), app(FlowMatcher::class)))
            ->handle(new MessageReceived($msg->id, $conv->id));

        $run->refresh();
        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->status);
    }

    public function test_comment_received_starts_comment_flow(): void
    {
        Queue::fake();

        $conv = Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_COMMENT, 'external_conversation_id' => 'cm2',
            'buyer_external_id' => 'b4', 'status' => 'open', 'message_count' => 1,
            'meta' => ['fb_post_id' => 'post123'],
        ]);
        $msg = Message::create([
            'tenant_id' => 1, 'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT, 'body' => 'hay quá', 'delivery_status' => Message::STATUS_SENT,
        ]);
        AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'CommentPost', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_COMMENT_ON_POST,
            'enabled' => true,
            'trigger_config' => ['post_ids' => ['post123']],
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [['source' => 't', 'target' => 'e', 'sourceHandle' => null]],
            ],
        ]);

        (new StartFlowOnComment(app(FlowEngine::class), app(FlowMatcher::class)))
            ->handle(new CommentReceived($msg->id, $conv->id));

        $this->assertSame(1, FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->where('status', FlowRun::STATUS_COMPLETED)->count());
    }

    public function test_comment_listener_ignores_dm_thread(): void
    {
        Queue::fake();

        $conv = Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c3',
            'buyer_external_id' => 'b5', 'status' => 'open', 'message_count' => 1,
        ]);
        $msg = Message::create([
            'tenant_id' => 1, 'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT, 'body' => 'hi', 'delivery_status' => Message::STATUS_SENT,
        ]);
        AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'CommentAny', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_COMMENT_ANY,
            'enabled' => true,
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [['source' => 't', 'target' => 'e', 'sourceHandle' => null]],
            ],
        ]);

        (new StartFlowOnComment(app(FlowEngine::class), app(FlowMatcher::class)))
            ->handle(new CommentReceived($msg->id, $conv->id));

        $this->assertSame(0, FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->count());
    }
}
