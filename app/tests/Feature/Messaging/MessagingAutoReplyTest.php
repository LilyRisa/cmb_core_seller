<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRun;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test auto-reply (SPEC-0024 S5): first_message + order_status + away_no_response
 * + idempotency/cooldown + rule CRUD. Engine standalone (route-around Phase 6.5).
 */
class MessagingAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        Queue::fake(); // không chạy SendMessage job — chỉ kiểm message được tạo

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'AutoShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_auto_1',
            'shop_name' => 'Auto Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function rule(array $attrs): AutoReplyRule
    {
        return AutoReplyRule::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'r',
            'enabled' => true,
            'applies_all_pages' => true,
            'cooldown_seconds' => 0,
            'priority' => 100,
        ], $attrs));
    }

    private function ingestInbound(string $convExt, string $msgExt, string $body): Conversation
    {
        $ingest = app(MessageIngestionService::class);
        $dto = new MessageDTO(
            externalConversationId: $convExt,
            externalMessageId: $msgExt,
            buyerExternalId: 'buyer_1',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: $body,
        );
        $res = $ingest->ingest($this->account, $dto);
        $ingest->fireEventsForNewMessage($res['conversation'], $res['message'], $res['created']);

        return $res['conversation'];
    }

    private function outboundCount(int $convId): int
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $convId)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->count();
    }

    public function test_first_message_rule_fires_on_inbound(): void
    {
        $rule = $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_FIRST_MESSAGE,
            'action' => ['kind' => 'raw', 'raw_text' => 'Chào mừng anh/chị đến với shop!'],
        ]);

        $conv = $this->ingestInbound('c1', 'm1', 'Xin chào');

        $msg = Message::withoutGlobalScopes()->where('conversation_id', $conv->id)->where('direction', 'outbound')->first();
        $this->assertNotNull($msg);
        $this->assertSame('Chào mừng anh/chị đến với shop!', $msg->body);
        $this->assertTrue((bool) $msg->sent_by_ai);
        $this->assertSame($rule->id, $msg->meta['auto_rule_id'] ?? null);

        $this->assertDatabaseHas('auto_reply_runs', [
            'rule_id' => $rule->id, 'conversation_id' => $conv->id,
            'window_key' => 'first', 'status' => AutoReplyRun::STATUS_FIRED,
        ]);
    }

    public function test_first_message_fires_only_once(): void
    {
        $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_FIRST_MESSAGE,
            'action' => ['kind' => 'raw', 'raw_text' => 'Hi'],
        ]);

        $conv = $this->ingestInbound('c2', 'm1', 'tin 1');
        $this->ingestInbound('c2', 'm2', 'tin 2');

        $this->assertSame(1, $this->outboundCount($conv->id));
    }

    public function test_order_status_rule_fires_via_engine(): void
    {
        $rule = $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_ORDER_STATUS,
            'trigger_config' => ['order_status' => 'delivered'],
            'action' => ['kind' => 'raw', 'raw_text' => 'Cảm ơn anh/chị đã nhận hàng!'],
        ]);

        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'c_os',
            'buyer_external_id' => 'b',
            'order_id' => 999,
            'status' => Conversation::STATUS_OPEN,
        ]);

        $engine = app(AutoReplyEngine::class);
        $engine->fire($conv->fresh(), AutoReplyRule::TRIGGER_ORDER_STATUS, ['order_id' => 999, 'order_status' => 'delivered']);

        $msg = Message::withoutGlobalScopes()->where('conversation_id', $conv->id)->where('direction', 'outbound')->first();
        $this->assertNotNull($msg);
        $this->assertSame('Cảm ơn anh/chị đã nhận hàng!', $msg->body);

        // Khác status ⇒ không fire (idempotency theo window_key order:id:status).
        $engine->fire($conv->fresh(), AutoReplyRule::TRIGGER_ORDER_STATUS, ['order_id' => 999, 'order_status' => 'delivered']);
        $this->assertSame(1, $this->outboundCount($conv->id));
    }

    public function test_away_no_response_fires_via_tick_command(): void
    {
        $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_AWAY_NO_RESPONSE,
            'trigger_config' => ['minutes' => 15],
            'action' => ['kind' => 'raw', 'raw_text' => 'Shop sẽ phản hồi sớm nhất ạ.'],
        ]);

        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'c_away',
            'buyer_external_id' => 'b',
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now()->subMinutes(30), // quá ngưỡng 15'
        ]);

        $this->artisan('messaging:auto-reply-tick')->assertSuccessful();

        $this->assertSame(1, $this->outboundCount($conv->id));
    }

    public function test_away_not_fired_if_recent_or_answered(): void
    {
        $this->rule([
            'trigger' => AutoReplyRule::TRIGGER_AWAY_NO_RESPONSE,
            'trigger_config' => ['minutes' => 15],
            'action' => ['kind' => 'raw', 'raw_text' => 'x'],
        ]);

        // Đã có outbound sau inbound ⇒ NV đã trả lời ⇒ không away.
        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'c_ans',
            'buyer_external_id' => 'b',
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now()->subMinutes(30),
            'last_outbound_at' => now()->subMinutes(5),
        ]);

        $this->artisan('messaging:auto-reply-tick')->assertSuccessful();

        $this->assertSame(0, $this->outboundCount($conv->id));
    }

    public function test_rule_crud_requires_manage_permission(): void
    {
        // Owner tạo được.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/auto-reply-rules', [
                'name' => 'Welcome', 'trigger' => 'first_message',
                'action' => ['kind' => 'raw', 'raw_text' => 'Hi'],
            ])->assertStatus(201);

        // staff_cs (có template.manage nhưng KHÔNG rule.manage) ⇒ 403.
        $cs = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($cs->getKey(), ['role' => Role::StaffCs->value]);
        $this->actingAs($cs)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/auto-reply-rules', [
                'name' => 'X', 'trigger' => 'first_message',
                'action' => ['kind' => 'raw', 'raw_text' => 'Hi'],
            ])->assertStatus(403);
    }
}
