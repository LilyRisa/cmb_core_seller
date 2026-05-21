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
 * TDD — POST /api/v1/messaging/conversations/{id}/unread (Phase A1).
 */
class MarkUnreadTest extends TestCase
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
        $this->tenant = Tenant::create(['name' => 'UnreadShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_unread_1',
            'shop_name' => 'Unread Shop',
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
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
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

    public function test_mark_unread_flags_conversation(): void
    {
        // Arrange: conversation already marked read (unread_count=0)
        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_unread_1',
            'buyer_external_id' => 'buyer_unread_1',
            'buyer_name' => 'Khách Test',
            'status' => Conversation::STATUS_OPEN,
            'unread_count' => 0,
            'message_count' => 1,
            'last_message_at' => now(),
        ]);

        // An inbound message that was already marked read
        $inbound = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $conv->id,
            'external_message_id' => 'msg_inbound_u1',
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Tin từ khách',
            'delivery_status' => Message::STATUS_SENT,
            'read_at' => now(),
        ]);

        // Act
        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/unread")
            ->assertOk();

        // Assert: unread_count bumped to >= 1
        $this->assertGreaterThanOrEqual(1, $conv->fresh()->unread_count);

        // Assert: inbound message's read_at is now null
        $this->assertNull($inbound->fresh()->read_at);

        // Assert: conversation appears in ?unread=1 filter
        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?unread=1')
            ->assertOk()
            ->assertJsonFragment(['id' => $conv->id]);
    }

    public function test_mark_unread_422_when_no_inbound(): void
    {
        // Arrange: conversation with NO inbound message
        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_unread_2',
            'buyer_external_id' => 'buyer_unread_2',
            'buyer_name' => 'Khách Test 2',
            'status' => Conversation::STATUS_OPEN,
            'unread_count' => 0,
            'message_count' => 0,
            'last_message_at' => now(),
        ]);

        // Only an outbound message — no inbound
        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $conv->id,
            'external_message_id' => 'msg_outbound_u1',
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Tin từ shop',
            'delivery_status' => Message::STATUS_SENT,
        ]);

        // Act + Assert
        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/unread")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'NO_INBOUND');
    }

    public function test_role_without_messaging_view_gets_403(): void
    {
        // StaffWarehouse không có messaging.view (xác nhận từ Role::StaffWarehouse->permissions()).
        $warehouseUser = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($warehouseUser->getKey(), ['role' => Role::StaffWarehouse->value]);

        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_403_unread',
            'buyer_external_id' => 'buyer_403',
            'buyer_name' => 'Khách 403',
            'status' => Conversation::STATUS_OPEN,
            'unread_count' => 0,
            'message_count' => 0,
            'last_message_at' => now(),
        ]);

        $this->actingAs($warehouseUser)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/unread")
            ->assertForbidden();
    }
}
