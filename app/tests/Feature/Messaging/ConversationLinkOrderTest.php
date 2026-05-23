<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `POST /messaging/conversations/{id}/link-order` — gắn đơn vừa tạo từ khung chat
 * vào hội thoại (để hiện icon đơn ở danh sách). Đơn phải thuộc tenant hiện tại.
 */
class ConversationLinkOrderTest extends TestCase
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
        $this->tenant = Tenant::create(['name' => 'LinkShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'manual',
            'external_shop_id' => 'shop_link_1', 'shop_name' => 'Link Shop',
            'status' => 'active', 'messaging_enabled' => true,
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

    private function seedConversation(): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_link_1',
            'buyer_external_id' => 'buyer_link_1',
            'buyer_name' => 'Chị Mua',
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
        ]);
    }

    private function seedOrder(?int $tenantId = null): Order
    {
        return Order::query()->create([
            'tenant_id' => $tenantId ?? $this->tenant->getKey(),
            'source' => 'manual',
            'status' => 'processing',
            'order_number' => 'MAN-'.uniqid(),
            'grand_total' => 150000,
            'is_cod' => true,
        ]);
    }

    public function test_links_order_to_conversation(): void
    {
        $conv = $this->seedConversation();
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", ['order_id' => $order->id])
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id);

        $this->assertDatabaseHas('conversations', ['id' => $conv->id, 'order_id' => $order->id]);
    }

    public function test_cannot_link_order_from_other_tenant(): void
    {
        $conv = $this->seedConversation();
        $otherTenant = Tenant::create(['name' => 'Other Shop']);
        $foreignOrder = $this->seedOrder($otherTenant->getKey());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", ['order_id' => $foreignOrder->id])
            ->assertStatus(404);

        $this->assertDatabaseHas('conversations', ['id' => $conv->id, 'order_id' => null]);
    }

    public function test_requires_order_id(): void
    {
        $conv = $this->seedConversation();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", [])
            ->assertStatus(422);
    }
}
