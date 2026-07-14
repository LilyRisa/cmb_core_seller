<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\ReportOrderConversionToMeta;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * `linkOrder()` phải dispatch ReportOrderConversionToMeta CHỈ khi: notify_customer=true
 * (đơn vừa tạo trong chat) — dù có/không ad_referral (job tự guard phần đó, xem
 * ReportOrderConversionToMetaJobTest). Test này chỉ xác nhận ĐIỂM DISPATCH đúng điều kiện.
 */
class FbConversionReportDispatchTest extends TestCase
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
        $this->tenant = Tenant::create(['name' => 'FbShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'page_1', 'shop_name' => 'My Page',
            'status' => 'active', 'messaging_enabled' => true,
        ]);
        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function seedConversation(array $extra = []): Conversation
    {
        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'PSID_123',
            'buyer_external_id' => 'PSID_123',
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
            'last_inbound_at' => now(),
        ], $extra));
    }

    private function seedOrder(): Order
    {
        return Order::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual', 'status' => 'processing',
            'order_number' => 'M-CTM-1', 'grand_total' => 150000, 'is_cod' => true,
        ]);
    }

    public function test_dispatches_when_notify_customer_true(): void
    {
        Queue::fake();
        $conv = $this->seedConversation(['meta' => ['ad_referral' => ['source' => 'ADS', 'ad_id' => 'AD_1']]]);
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", [
                'order_id' => $order->id, 'notify_customer' => true,
            ])->assertOk();

        Queue::assertPushed(ReportOrderConversionToMeta::class, fn ($job) => $job->conversationId === $conv->id && $job->orderId === $order->id);
    }

    public function test_does_not_dispatch_without_notify_customer_flag(): void
    {
        Queue::fake();
        $conv = $this->seedConversation(['meta' => ['ad_referral' => ['source' => 'ADS', 'ad_id' => 'AD_1']]]);
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", ['order_id' => $order->id])
            ->assertOk();

        Queue::assertNotPushed(ReportOrderConversionToMeta::class);
    }
}
