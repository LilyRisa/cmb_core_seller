<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Node AI trả lời (S4): nhánh mặc định khi AI trả lời được, nhánh 'handoff' khi
 * escalate/lỗi. AiSuggestionService được mock để test 2 nhánh độc lập với provider thật.
 */
class AiReplyNodeTest extends TestCase
{
    use RefreshDatabase;

    private function conv(): Conversation
    {
        return Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 0,
        ]);
    }

    private function flow(): AutomationFlow
    {
        return AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'F', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'enabled' => true,
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 'ai', 'type' => 'ai_reply', 'data' => []],
                    ['id' => 'ok', 'type' => 'send_message', 'data' => ['text' => 'REPLIED_PATH']],
                    ['id' => 'human', 'type' => 'send_message', 'data' => ['text' => 'HANDOFF_PATH']],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [
                    ['source' => 't', 'target' => 'ai', 'sourceHandle' => null],
                    ['source' => 'ai', 'target' => 'ok', 'sourceHandle' => null],
                    ['source' => 'ai', 'target' => 'human', 'sourceHandle' => 'handoff'],
                    ['source' => 'ok', 'target' => 'e', 'sourceHandle' => null],
                    ['source' => 'human', 'target' => 'e', 'sourceHandle' => null],
                ],
            ],
        ]);
    }

    private function bodies(int $convId): array
    {
        return Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $convId)->pluck('body')->all();
    }

    public function test_default_branch_when_ai_replies(): void
    {
        Queue::fake();
        $mock = \Mockery::mock(AiSuggestionService::class);
        $mock->shouldReceive('autoRespond')->once()->andReturn(['action' => 'sent', 'intent' => 'general']);
        $this->app->instance(AiSuggestionService::class, $mock);

        $conv = $this->conv();
        $run = app(FlowEngine::class)->start($this->flow(), $conv, inboundBody: 'cho hỏi giá');

        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertContains('REPLIED_PATH', $this->bodies((int) $conv->id));
        $this->assertNotContains('HANDOFF_PATH', $this->bodies((int) $conv->id));
    }

    public function test_handoff_branch_when_ai_escalates(): void
    {
        Queue::fake();
        $mock = \Mockery::mock(AiSuggestionService::class);
        $mock->shouldReceive('autoRespond')->once()->andReturn(['action' => 'escalated', 'intent' => 'complaint']);
        $this->app->instance(AiSuggestionService::class, $mock);

        $conv = $this->conv();
        $run = app(FlowEngine::class)->start($this->flow(), $conv, inboundBody: 'tôi muốn khiếu nại');

        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertContains('HANDOFF_PATH', $this->bodies((int) $conv->id));
        $this->assertNotContains('REPLIED_PATH', $this->bodies((int) $conv->id));
    }

    public function test_handoff_branch_when_ai_throws(): void
    {
        Queue::fake();
        $mock = \Mockery::mock(AiSuggestionService::class);
        $mock->shouldReceive('autoRespond')->once()->andThrow(new \RuntimeException('provider down'));
        $this->app->instance(AiSuggestionService::class, $mock);

        $conv = $this->conv();
        $run = app(FlowEngine::class)->start($this->flow(), $conv, inboundBody: 'hi');

        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertContains('HANDOFF_PATH', $this->bodies((int) $conv->id));
    }
}
