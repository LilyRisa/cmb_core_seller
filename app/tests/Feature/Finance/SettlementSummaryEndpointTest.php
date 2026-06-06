<?php

namespace Tests\Feature\Finance;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Endpoint GET /api/v1/settlements/summary — gating finance_settlements + tổng hợp. SPEC 2026-06-06. */
class SettlementSummaryEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activate(string $planCode): void
    {
        Subscription::query()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->copy()->addMonth(),
        ]);
    }

    public function test_blocked_when_plan_lacks_finance_feature(): void
    {
        $this->activate('starter');   // starter: finance_settlements = false
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/settlements/summary')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_returns_summary_for_pro(): void
    {
        $this->activate('pro');
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 's1', 'shop_name' => 'S1', 'status' => 'active',
        ]);
        Settlement::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(), 'currency' => 'VND',
            'external_id' => 'A', 'period_start' => CarbonImmutable::now()->subDays(5), 'period_end' => CarbonImmutable::now()->subDays(2),
            'total_payout' => 800000, 'total_revenue' => 1000000, 'total_fee' => -150000, 'total_shipping_fee' => -50000, 'status' => 'reconciled',
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/settlements/summary?from='.CarbonImmutable::now()->subDays(10)->toDateString().'&to='.CarbonImmutable::now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.totals.settlements', 1)
            ->assertJsonPath('data.totals.payout', 800000)
            ->assertJsonPath('data.totals.reconciled', 1);
    }
}
