<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Listeners\AiAutoModeOnInbound;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Cổng ưu tiên AI (ADR-0022): AI là Tầng 2 (cuối) — nhường Tầng 1 (flow đang chạy,
 * flow/rule first_message/keyword khớp) + công tắc tự gửi tách theo nhóm kênh.
 */
class MessagingAiPriorityGateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        Queue::fake();

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'GateShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_BUSINESS)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'manual',
            'external_shop_id' => 's', 'shop_name' => 's', 'status' => 'active', 'messaging_enabled' => true,
        ]);
    }

    private function settings(bool $marketplace = true, bool $facebook = true): void
    {
        // Không có tenant context trong test listener ⇒ bỏ global scope khi upsert.
        MessagingSetting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => $this->tenant->getKey()], [
            'ai_provider_code' => 'manual', 'ai_enabled' => true,
            'auto_mode_marketplace' => $marketplace, 'auto_mode_facebook' => $facebook,
        ]);
    }

    private function conversation(string $provider = 'manual'): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->id, 'provider' => $provider,
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c'.uniqid(),
            'buyer_external_id' => 'b', 'buyer_name' => 'Khách', 'status' => Conversation::STATUS_OPEN,
            'message_count' => 1, 'last_inbound_at' => now(),
        ]);
    }

    private function inbound(Conversation $conv, string $body): Message
    {
        return Message::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_INBOUND, 'kind' => Message::KIND_TEXT, 'body' => $body,
        ]);
    }

    private function runListener(Conversation $conv, Message $msg): void
    {
        app(AiAutoModeOnInbound::class)->handle(new MessageReceived($msg->id, $conv->id));
    }

    private function outboundCount(Conversation $conv): int
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_OUTBOUND)->count();
    }

    public function test_ai_runs_when_no_tier1_handler_matches(): void
    {
        $this->settings();
        $conv = $this->conversation();
        $msg = $this->inbound($conv, 'Đơn của em bao giờ giao ạ?');

        $this->runListener($conv, $msg);

        $this->assertSame(1, $this->outboundCount($conv));
    }

    public function test_ai_skipped_when_first_message_flow_matches(): void
    {
        $this->settings();
        $conv = $this->conversation(); // message_count = 1 ⇒ first_message khớp
        AutomationFlow::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Chào', 'provider' => 'manual',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            'trigger_config' => [], 'enabled' => true, 'version' => 1,
            'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        ]);
        $msg = $this->inbound($conv, 'Xin chào shop');

        $this->runListener($conv, $msg);

        $this->assertSame(0, $this->outboundCount($conv));
    }

    public function test_ai_skipped_when_keyword_rule_matches(): void
    {
        $this->settings();
        $conv = $this->conversation();
        $conv->update(['message_count' => 3]); // không phải tin đầu — chỉ test nhánh keyword
        AutoReplyRule::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Giá', 'trigger' => AutoReplyRule::TRIGGER_KEYWORD,
            'trigger_config' => ['keywords' => ['giá']], 'filter' => [],
            'action' => ['kind' => AutoReplyRule::ACTION_RAW, 'raw_text' => 'Dạ giá là...'],
            'cooldown_seconds' => 0, 'enabled' => true, 'priority' => 100,
        ]);
        $msg = $this->inbound($conv, 'cho hỏi GIÁ bao nhiêu');

        $this->runListener($conv, $msg);

        $this->assertSame(0, $this->outboundCount($conv));
    }

    public function test_ai_skipped_when_flow_run_active(): void
    {
        $this->settings();
        $conv = $this->conversation();
        $conv->update(['message_count' => 5]);
        $flow = AutomationFlow::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'F', 'provider' => 'manual',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_KEYWORD,
            'trigger_config' => ['keywords' => ['zzz']], 'enabled' => true, 'version' => 1,
            'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        ]);
        FlowRun::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'flow_id' => $flow->id, 'conversation_id' => $conv->id,
            'status' => FlowRun::STATUS_WAITING, 'current_node_id' => 't', 'context' => [],
        ]);
        $msg = $this->inbound($conv, 'tin nhắn bất kỳ không khớp từ khoá');

        $this->runListener($conv, $msg);

        $this->assertSame(0, $this->outboundCount($conv));
    }

    public function test_marketplace_toggle_independent_from_facebook(): void
    {
        // Bật sàn, tắt Facebook ⇒ inbound facebook KHÔNG được AI trả lời.
        $this->settings(marketplace: true, facebook: false);
        $fbAccount = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'fb', 'shop_name' => 'fb', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $fbAccount->id, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'fbc',
            'buyer_external_id' => 'b', 'status' => Conversation::STATUS_OPEN, 'message_count' => 3, 'last_inbound_at' => now(),
        ]);
        $msg = $this->inbound($conv, 'Đơn của em bao giờ giao ạ?');

        $this->runListener($conv, $msg);
        $this->assertSame(0, $this->outboundCount($conv));

        // Bật Facebook ⇒ AI trả lời.
        $this->settings(marketplace: true, facebook: true);
        $this->runListener($conv, $msg);
        $this->assertSame(1, $this->outboundCount($conv));
    }
}
