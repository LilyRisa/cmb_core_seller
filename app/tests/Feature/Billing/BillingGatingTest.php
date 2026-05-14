<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6.4 — SPEC 0018 §3.6: middleware gating hạn mức + feature.
 */
class BillingGatingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'GatingShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateSubscription(string $planCode): Subscription
    {
        Subscription::query()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        $now = now();

        return Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function preActiveChannelAccounts(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            ChannelAccount::query()->create([
                'tenant_id' => $this->tenant->getKey(),
                'provider' => 'tiktok',
                'external_shop_id' => 'shop'.$i.uniqid(),
                'shop_name' => "Shop {$i}",
                'status' => ChannelAccount::STATUS_ACTIVE,
            ]);
        }
    }

    public function test_starter_plan_blocks_third_channel_account_connect(): void
    {
        $this->activateSubscription(Plan::CODE_STARTER);
        $this->preActiveChannelAccounts(2);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/channel-accounts/tiktok/connect');

        $resp->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_LIMIT_REACHED')
            ->assertJsonPath('error.details.resource', 'channel_accounts')
            ->assertJsonPath('error.details.limit', 2)
            ->assertJsonPath('error.details.current', 2);
    }

    public function test_pro_plan_allows_up_to_five_channel_accounts(): void
    {
        $this->activateSubscription(Plan::CODE_PRO);
        $this->preActiveChannelAccounts(4);

        // Gọi đến route ⇒ middleware qua (current=4 < limit=5). Endpoint thật có thể fail vì shop chưa
        // có TikTok credential — em chỉ kiểm middleware không trả 402.
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/channel-accounts/tiktok/connect');

        $this->assertNotSame(402, $resp->status(), 'Middleware không được chặn khi current < limit.');
    }

    public function test_starter_plan_blocks_finance_settlements_endpoint(): void
    {
        $this->activateSubscription(Plan::CODE_STARTER);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/settlements')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED')
            ->assertJsonPath('error.details.features.0', 'finance_settlements');
    }

    public function test_pro_plan_allows_finance_settlements_endpoint(): void
    {
        $this->activateSubscription(Plan::CODE_PRO);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/settlements')
            ->assertOk();
    }

    public function test_starter_plan_blocks_procurement_suppliers_endpoint(): void
    {
        $this->activateSubscription(Plan::CODE_STARTER);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/suppliers')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_pro_plan_allows_procurement_suppliers_endpoint(): void
    {
        $this->activateSubscription(Plan::CODE_PRO);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/suppliers')
            ->assertOk();
    }

    public function test_starter_plan_blocks_reports_profit_but_allows_revenue(): void
    {
        $this->activateSubscription(Plan::CODE_STARTER);

        // Revenue mở cho mọi gói.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/reports/revenue')
            ->assertOk();

        // Profit cần feature `profit_reports`.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/reports/profit')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_trial_plan_blocks_demand_planning(): void
    {
        // Trial subscription mặc định.
        $plan = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_TRIALING,
            'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/procurement/demand-planning')
            ->assertStatus(402);
    }
}
