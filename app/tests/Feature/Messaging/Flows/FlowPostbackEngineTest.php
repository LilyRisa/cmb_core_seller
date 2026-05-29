<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Events\PostbackReceived;
use CMBcoreSeller\Modules\Messaging\Listeners\AdvanceFlowOnPostback;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Flow Builder S2 E2E: node send_buttons gửi tin nút bấm rồi chờ; postback resume
 * đúng nhánh handle; bấm nút sai node bị bỏ qua; provider thiếu năng lực ⇒ node fail.
 */
class FlowPostbackEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => 'secret',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    private function conv(string $provider = 'facebook_page'): Conversation
    {
        return Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => $provider,
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 0,
        ]);
    }

    /** @param array<string,mixed> $graph */
    private function flow(array $graph): AutomationFlow
    {
        return AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'F', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE,
            'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'graph' => $graph, 'enabled' => true,
        ]);
    }

    /** @return array<string,mixed> */
    private function buttonGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'ask', 'type' => 'send_buttons', 'data' => [
                    'text' => 'Bạn cần gì ạ?',
                    'buttons' => [
                        ['id' => 'b_buy', 'label' => 'Mua hàng', 'type' => 'postback'],
                        ['id' => 'b_ship', 'label' => 'Phí ship', 'type' => 'postback'],
                        ['id' => 'b_web', 'label' => 'Xem web', 'type' => 'url', 'url' => 'https://shop.vn'],
                    ],
                ]],
                ['id' => 'buy', 'type' => 'send_message', 'data' => ['text' => 'Mời bạn đặt hàng']],
                ['id' => 'ship', 'type' => 'send_message', 'data' => ['text' => 'Phí ship 30k toàn quốc.']],
                ['id' => 'end', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 'ask', 'sourceHandle' => null],
                ['source' => 'ask', 'target' => 'buy', 'sourceHandle' => 'b_buy'],
                ['source' => 'ask', 'target' => 'ship', 'sourceHandle' => 'b_ship'],
                ['source' => 'ask', 'target' => 'end', 'sourceHandle' => null],
                ['source' => 'buy', 'target' => 'end', 'sourceHandle' => null],
                ['source' => 'ship', 'target' => 'end', 'sourceHandle' => null],
            ],
        ];
    }

    public function test_send_buttons_then_resume_by_postback_handle(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $flow = $this->flow($this->buttonGraph());
        $engine = app(FlowEngine::class);

        $run = $engine->start($flow, $conv, inboundBody: 'hi');

        // Gửi tin nút bấm rồi dừng chờ ở node 'ask'.
        $this->assertSame(FlowRun::STATUS_WAITING, $run->fresh()->status);
        $this->assertSame('ask', $run->fresh()->current_node_id);

        $msg = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->where('kind', Message::KIND_INTERACTIVE)->first();
        $this->assertNotNull($msg);
        $this->assertSame('Bạn cần gì ạ?', $msg->body);
        $payloads = array_column($msg->meta['interactive']['buttons'], 'payload');
        $this->assertContains(FlowPostbackPayload::encode('ask', 'b_buy'), $payloads);
        $this->assertContains(FlowPostbackPayload::encode('ask', 'b_ship'), $payloads);
        // Nút url không mang payload postback.
        $urlButton = collect($msg->meta['interactive']['buttons'])->firstWhere('type', 'url');
        $this->assertSame('https://shop.vn', $urlButton['url']);

        // Buyer bấm "Phí ship" → resume nhánh b_ship → gửi "Phí ship 30k..." → kết thúc.
        $engine->resume($run->fresh(), $conv->fresh(), null, 'b_ship');

        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $conv->id)->where('body', 'Phí ship 30k toàn quốc.')->count());
        $this->assertSame(0, Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $conv->id)->where('body', 'Mời bạn đặt hàng')->count());
    }

    public function test_postback_for_wrong_node_is_ignored(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $flow = $this->flow($this->buttonGraph());
        $engine = app(FlowEngine::class);

        $run = $engine->start($flow, $conv, inboundBody: 'hi');
        $this->assertSame('ask', $run->fresh()->current_node_id);

        // Postback trỏ node KHÁC (vd node cũ) ⇒ stale guard ⇒ không resume.
        $listener = new AdvanceFlowOnPostback($engine);
        $listener->handle(new PostbackReceived((int) $conv->id, FlowPostbackPayload::encode('some_old_node', 'b_buy')));

        $this->assertSame(FlowRun::STATUS_WAITING, $run->fresh()->status);
        $this->assertSame('ask', $run->fresh()->current_node_id);
        $this->assertSame(0, Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $conv->id)
            ->whereIn('body', ['Mời bạn đặt hàng', 'Phí ship 30k toàn quốc.'])->count());
    }

    public function test_unsupported_provider_fails_node_without_sending(): void
    {
        Queue::fake();
        // 'manual' connector không hỗ trợ outbound.interactive.
        $conv = $this->conv('manual');
        $flow = $this->flow($this->buttonGraph());
        $engine = app(FlowEngine::class);

        $run = $engine->start($flow, $conv, inboundBody: 'hi');

        $this->assertSame(FlowRun::STATUS_FAILED, $run->fresh()->status);
        $this->assertSame('interactive_unsupported', $run->fresh()->error);
        $this->assertSame(0, Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $conv->id)->where('kind', Message::KIND_INTERACTIVE)->count());
    }
}
