<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FlowEngineTest extends TestCase
{
    use RefreshDatabase;

    private function conv(): Conversation
    {
        return Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 1,
        ]);
    }

    private function flow(array $graph): AutomationFlow
    {
        return AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'F', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE,
            'trigger_type' => AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            'graph' => $graph, 'enabled' => true,
        ]);
    }

    public function test_start_runs_send_then_wait_then_resume_branches(): void
    {
        Queue::fake();
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'hello', 'type' => 'send_message', 'data' => ['text' => 'Xin chào']],
                ['id' => 'w', 'type' => 'wait_reply', 'data' => []],
                ['id' => 'cond', 'type' => 'condition', 'data' => ['keywords' => ['giá'], 'match' => 'any']],
                ['id' => 'price', 'type' => 'send_message', 'data' => ['text' => 'Giá 100k']],
                ['id' => 'bye', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 'hello', 'sourceHandle' => null],
                ['source' => 'hello', 'target' => 'w', 'sourceHandle' => null],
                ['source' => 'w', 'target' => 'cond', 'sourceHandle' => null],
                ['source' => 'cond', 'target' => 'price', 'sourceHandle' => 'match'],
                ['source' => 'cond', 'target' => 'bye', 'sourceHandle' => 'no_match'],
                ['source' => 'price', 'target' => 'bye', 'sourceHandle' => null],
            ],
        ];
        $conv = $this->conv();
        $flow = $this->flow($graph);
        $engine = app(FlowEngine::class);

        $run = $engine->start($flow, $conv, inboundBody: 'hi');
        $this->assertSame(FlowRun::STATUS_WAITING, $run->fresh()->status);
        $this->assertSame('w', $run->fresh()->current_node_id);
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $conv->id)->where('body', 'Xin chào')->count());

        $engine->resume($run->fresh(), $conv->fresh(), inboundBody: 'cho hỏi giá');
        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $conv->id)->where('body', 'Giá 100k')->count());
    }

    public function test_start_is_idempotent_when_active_run_exists(): void
    {
        Queue::fake();
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'w', 'type' => 'wait_reply', 'data' => []],
            ],
            'edges' => [['source' => 't', 'target' => 'w', 'sourceHandle' => null]],
        ];
        $conv = $this->conv();
        $flow = $this->flow($graph);
        $engine = app(FlowEngine::class);

        $engine->start($flow, $conv, inboundBody: 'hi');
        $engine->start($flow, $conv->fresh(), inboundBody: 'again');

        $this->assertSame(1, FlowRun::withoutGlobalScope(TenantScope::class)->where('flow_id', $flow->id)->where('conversation_id', $conv->id)->count());
    }
}
