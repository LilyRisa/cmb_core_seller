<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\SendMessageNodeExecutor;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Task 3 — SendMessageNodeExecutor duyệt steps[] (idempotent, giữ nhánh cũ).
 *
 * 4 case:
 *   1. Node [send_text, send_text] → 2 tin, trả advance(null).
 *   2. Chạy lại với cursor=2 → KHÔNG gửi lại (idempotent), trả advance(null).
 *   3. Node [send_text, send_buttons] provider interactive → 2 tin, trả wait();
 *      payload postback encode NODE id thật (không phải step id).
 *   4. Node KHÔNG steps (data cũ: text thẳng) → hành vi cũ, 1 tin, trả advance.
 */
class SteppedNodeExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Enable facebook_page connector (implements InteractiveMessagingConnector)
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => 'secret',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function conv(string $provider = 'facebook_page'): Conversation
    {
        return Conversation::create([
            'tenant_id' => 1,
            'channel_account_id' => 1,
            'provider' => $provider,
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1',
            'status' => 'open',
            'message_count' => 0,
        ]);
    }

    /** @param array<string,mixed> $context */
    private function makeRun(Conversation $conv, array $context = []): FlowRun
    {
        $flow = AutomationFlow::create([
            'tenant_id' => 1,
            'name' => 'F',
            'provider' => $conv->provider,
            'status' => AutomationFlow::STATUS_ACTIVE,
            'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'graph' => ['nodes' => [], 'edges' => []],
            'enabled' => true,
        ]);

        return FlowRun::create([
            'tenant_id' => 1,
            'flow_id' => $flow->id,
            'conversation_id' => $conv->id,
            'status' => FlowRun::STATUS_ACTIVE,
            'current_node_id' => null,
            'context' => $context,
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $steps
     */
    private function node(string $id, array $steps): FlowNode
    {
        return new FlowNode($id, 'send_message', ['steps' => $steps]);
    }

    private function executor(): SendMessageNodeExecutor
    {
        return app(SendMessageNodeExecutor::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Case 1: [send_text, send_text] → 2 messages, advance(null)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_two_text_steps_sends_both_messages_and_advances(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $run = $this->makeRun($conv);
        $ctx = new FlowContext($conv, $run);

        $node = $this->node('n1', [
            ['id' => 's1', 'type' => 'send_text', 'text' => 'Xin chào bạn'],
            ['id' => 's2', 'type' => 'send_text', 'text' => 'Bạn cần gì ạ?'],
        ]);

        $result = $this->executor()->execute($node, $ctx);

        $this->assertTrue($result->isAdvance());
        $this->assertNull($result->handle);
        $this->assertSame(
            2,
            Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conv->id)
                ->count(),
        );
        $this->assertSame(
            1,
            Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conv->id)
                ->where('body', 'Xin chào bạn')
                ->count(),
        );
        $this->assertSame(
            1,
            Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conv->id)
                ->where('body', 'Bạn cần gì ạ?')
                ->count(),
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Case 2: cursor=2 → skip all steps (idempotent), advance(null), 0 messages
    // ──────────────────────────────────────────────────────────────────────────

    public function test_cursor_at_end_skips_all_steps_and_advances(): void
    {
        Queue::fake();
        $conv = $this->conv();
        // Pre-seed cursor = 2 (both steps already done)
        $run = $this->makeRun($conv, ['_step_sent' => ['n1' => 2]]);
        $ctx = new FlowContext($conv, $run);

        $node = $this->node('n1', [
            ['id' => 's1', 'type' => 'send_text', 'text' => 'Xin chào bạn'],
            ['id' => 's2', 'type' => 'send_text', 'text' => 'Bạn cần gì ạ?'],
        ]);

        $result = $this->executor()->execute($node, $ctx);

        $this->assertTrue($result->isAdvance());
        $this->assertSame(
            0,
            Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conv->id)
                ->count(),
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Case 3: [send_text, send_buttons] interactive → 2 messages, wait;
    //         postback payload encodes real NODE id (not step id)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_text_then_buttons_sends_both_and_returns_wait(): void
    {
        Queue::fake();
        $conv = $this->conv('facebook_page');
        $run = $this->makeRun($conv);
        $ctx = new FlowContext($conv, $run);

        $node = $this->node('n_ask', [
            ['id' => 's1', 'type' => 'send_text', 'text' => 'Xin chào bạn'],
            ['id' => 's2', 'type' => 'send_buttons', 'text' => 'Bạn cần gì?', 'buttons' => [
                ['id' => 'b_buy', 'label' => 'Mua hàng', 'type' => 'postback'],
                ['id' => 'b_ship', 'label' => 'Phí ship', 'type' => 'postback'],
            ]],
        ]);

        $result = $this->executor()->execute($node, $ctx);

        $this->assertTrue($result->isWait());
        $this->assertFalse($result->isFail());
        $this->assertSame(
            2,
            Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conv->id)
                ->count(),
        );

        // Postback payload phải encode NODE id thật ('n_ask'), không phải step id ('s2').
        $interactive = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('kind', Message::KIND_INTERACTIVE)
            ->first();
        $this->assertNotNull($interactive);
        $payloads = array_column($interactive->meta['interactive']['buttons'], 'payload');
        $this->assertContains(FlowPostbackPayload::encode((string) $run->flow_id, 'n_ask', 'b_buy'), $payloads);
        $this->assertContains(FlowPostbackPayload::encode((string) $run->flow_id, 'n_ask', 'b_ship'), $payloads);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Case 4: legacy node WITHOUT steps → existing behavior (1 message, advance)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_legacy_node_without_steps_uses_existing_behavior(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $run = $this->makeRun($conv);
        $ctx = new FlowContext($conv, $run);

        // Node cũ: text thẳng vào data, KHÔNG có steps[]
        $node = new FlowNode('n_legacy', 'send_message', ['text' => 'Tin không có steps']);

        $result = $this->executor()->execute($node, $ctx);

        $this->assertTrue($result->isAdvance());
        $this->assertSame(
            1,
            Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conv->id)
                ->where('body', 'Tin không có steps')
                ->count(),
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Case 5: data has BOTH legacy text AND steps:[] (explicit empty key)
    //         → steps key is authoritative → sends NOTHING, advance(null)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_explicit_empty_steps_with_legacy_text_sends_nothing(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $run = $this->makeRun($conv);
        $ctx = new FlowContext($conv, $run);

        // Node có cả legacy text VÀ steps:[] rỗng tường minh
        // → steps key ưu tiên → không gửi gì (không "hồi sinh" text cũ)
        $node = new FlowNode('n_empty', 'send_message', ['text' => 'Hello', 'steps' => []]);

        $result = $this->executor()->execute($node, $ctx);

        $this->assertTrue($result->isAdvance());
        $this->assertNull($result->handle);
        $this->assertSame(
            0,
            Message::withoutGlobalScope(TenantScope::class)
                ->where('conversation_id', $conv->id)
                ->count(),
            'Legacy text không được gửi khi key steps tồn tại (dù rỗng)',
        );
    }
}
