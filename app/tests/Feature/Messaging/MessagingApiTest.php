<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test REST inbox API + Billing gating + RBAC cho Messaging.
 *
 * Scenarios:
 *   - Tenant gói trial (không có feature messaging_inbox) ⇒ 402 PLAN_FEATURE_LOCKED
 *   - Tenant gói Pro: list conversations OK
 *   - StaffWarehouse không có `messaging.view` ⇒ 403
 *   - Send text → tạo message pending + dispatch SendMessage job
 *   - Mark read → reset unread_count
 */
class MessagingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'MsgShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_api_1',
            'shop_name' => 'API Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateSubscription(string $planCode): void
    {
        Subscription::query()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        $now = now();

        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function seedConversation(): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_api_1',
            'buyer_external_id' => 'buyer_1',
            'buyer_name' => 'Anh Khách',
            'status' => Conversation::STATUS_OPEN,
            'unread_count' => 2,
            'message_count' => 2,
            'last_message_at' => now(),
            'last_message_preview' => 'Hello',
            'last_inbound_at' => now()->subMinutes(5),
        ]);
    }

    public function test_starter_plan_cannot_access_inbox(): void
    {
        $this->activateSubscription(Plan::CODE_STARTER);

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_pro_plan_can_list_conversations(): void
    {
        $this->seedConversation();

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.buyer_name', 'Anh Khách')
            ->assertJsonPath('data.0.unread_count', 2);
    }

    public function test_staff_warehouse_cannot_view_inbox(): void
    {
        $sw = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($sw->getKey(), ['role' => Role::StaffWarehouse->value]);

        $this->actingAs($sw)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertStatus(403);
    }

    public function test_staff_cs_can_view_and_reply(): void
    {
        $cs = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($cs->getKey(), ['role' => Role::StaffCs->value]);

        $conv = $this->seedConversation();

        $this->actingAs($cs)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertOk();

        $this->actingAs($cs)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/messages", ['body' => 'Em xin chào'])
            ->assertStatus(202)
            ->assertJsonPath('data.body', 'Em xin chào');
    }

    public function test_send_text_creates_message_pending(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $conv = $this->seedConversation();

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/messages", ['body' => 'Test reply'])
            ->assertStatus(202)
            ->assertJsonPath('data.direction', Message::DIRECTION_OUTBOUND)
            ->assertJsonPath('data.delivery_status', Message::STATUS_PENDING);

        $this->assertSame(1, Message::query()
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->count());

        \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\Messaging\Jobs\SendMessage::class);
    }

    public function test_mark_read_resets_unread_count(): void
    {
        $conv = $this->seedConversation();
        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $conv->id,
            'external_message_id' => 'msg_inbound_1',
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Buyer msg',
            'delivery_status' => Message::STATUS_SENT,
        ]);

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/read")
            ->assertOk();

        $this->assertSame(0, $conv->fresh()->unread_count);
        $this->assertNotNull(Message::query()->where('conversation_id', $conv->id)->where('direction', 'inbound')->first()->read_at);
    }
}
