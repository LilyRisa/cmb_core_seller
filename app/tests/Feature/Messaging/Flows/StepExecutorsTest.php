<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\FlowStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\SendButtonsStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\SendMediaStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\SendTextStep;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Task 2 — 3 step executors: send_text / send_media / send_buttons.
 *
 * Connector fake: mirror FlowPostbackEngineTest — config enable facebook_page
 * (hỗ trợ outbound.interactive) + forget singleton registry. 'manual' provider
 * không được đăng ký ⇒ has() = false ⇒ non-interactive path.
 */
class StepExecutorsTest extends TestCase
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

    private function makeRun(Conversation $conv): FlowRun
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
            'current_node_id' => 'node1',
            'context' => [],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // send_text
    // ──────────────────────────────────────────────────────────────────────────

    public function test_send_text_creates_outbound_message_and_returns_done(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $ctx = new FlowContext($conv, $this->makeRun($conv));
        $step = FlowStep::fromArray(['id' => 's1', 'type' => SendTextStep::TYPE, 'text' => 'Xin chào bạn']);

        $result = app(SendTextStep::class)->execute($step, $ctx);

        $this->assertTrue($result->isDone());
        $this->assertFalse($result->isWait());
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('body', 'Xin chào bạn')
            ->count());
    }

    public function test_send_text_empty_string_skips_without_creating_message(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $ctx = new FlowContext($conv, $this->makeRun($conv));
        $step = FlowStep::fromArray(['id' => 's1', 'type' => SendTextStep::TYPE, 'text' => '   ']);

        $result = app(SendTextStep::class)->execute($step, $ctx);

        $this->assertTrue($result->isDone());
        $this->assertSame(0, Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)->count());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // send_media
    // ──────────────────────────────────────────────────────────────────────────

    public function test_send_media_creates_outbound_media_message_and_returns_done(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $ctx = new FlowContext($conv, $this->makeRun($conv));
        $step = FlowStep::fromArray([
            'id' => 's2',
            'type' => SendMediaStep::TYPE,
            'kind' => 'image',
            'attachment' => [
                'storage_path' => 'tenants/1/messaging/flows/1/photo.jpg',
                'mime' => 'image/jpeg',
                'filename' => 'photo.jpg',
            ],
        ]);

        $result = app(SendMediaStep::class)->execute($step, $ctx);

        $this->assertTrue($result->isDone());
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('kind', 'image')
            ->count());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // send_buttons — provider KHÔNG hỗ trợ interactive
    // ──────────────────────────────────────────────────────────────────────────

    public function test_send_buttons_on_non_interactive_provider_returns_fail_and_creates_no_message(): void
    {
        Queue::fake();
        // 'manual' không đăng ký trong MessagingRegistry ⇒ has()=false ⇒ non-interactive
        $conv = $this->conv('manual');
        $ctx = new FlowContext($conv, $this->makeRun($conv));
        $step = FlowStep::fromArray([
            'id' => 's3',
            'type' => SendButtonsStep::TYPE,
            'text' => 'Bạn cần gì?',
            'buttons' => [
                ['id' => 'b1', 'label' => 'Mua hàng', 'type' => 'postback'],
            ],
        ]);

        $result = app(SendButtonsStep::class)->execute($step, $ctx);

        $this->assertTrue($result->isFail());
        $this->assertSame('interactive_unsupported', $result->error());
        $this->assertSame(0, Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->count());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // send_buttons — provider HỖ TRỢ interactive (facebook_page)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_send_buttons_on_interactive_provider_creates_message_and_returns_wait(): void
    {
        Queue::fake();
        $conv = $this->conv('facebook_page');
        $ctx = new FlowContext($conv, $this->makeRun($conv));
        $step = FlowStep::fromArray([
            'id' => 's4',
            'type' => SendButtonsStep::TYPE,
            'text' => 'Bạn cần gì ạ?',
            'buttons' => [
                ['id' => 'b_buy', 'label' => 'Mua hàng', 'type' => 'postback'],
                ['id' => 'b_ship', 'label' => 'Phí ship', 'type' => 'postback'],
            ],
        ]);

        $result = app(SendButtonsStep::class)->execute($step, $ctx);

        $this->assertTrue($result->isWait());
        $this->assertFalse($result->isFail());
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('kind', Message::KIND_INTERACTIVE)
            ->count());
    }
}
