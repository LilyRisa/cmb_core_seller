<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\AccountingPostRule;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7.1 — SPEC 0019 §3.1.
 *
 * `POST /accounting/setup` seed CoA TT133 + 12 periods + post rules; idempotent.
 * Gating: plan thấp → 402.
 */
class AccountingSetupTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAccountingTenant();
    }

    public function test_status_returns_false_before_setup(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/setup/status')
            ->assertOk()
            ->assertJsonPath('data.initialized', false);
    }

    public function test_setup_seeds_chart_of_accounts_periods_and_rules(): void
    {
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/setup', ['year' => 2026]);

        $resp->assertOk();
        $tenantId = $this->tenant->getKey();
        $this->assertGreaterThan(60, ChartAccount::query()->where('tenant_id', $tenantId)->count(),
            'CoA TT133 phải có ≥60 TK.');
        $this->assertSame(17, FiscalPeriod::query()->where('tenant_id', $tenantId)->count(),
            '12 tháng + 4 quý + 1 năm = 17 periods.');
        $this->assertGreaterThan(10, AccountingPostRule::query()->where('tenant_id', $tenantId)->count(),
            'Mapping rules cần ít nhất 10 cặp (Phase 7.1-7.4).');

        // Status sau setup = true.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/setup/status')
            ->assertJsonPath('data.initialized', true);
    }

    public function test_setup_is_idempotent(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/accounting/setup', ['year' => 2026])->assertOk();
        $tenantId = $this->tenant->getKey();
        $accCount = ChartAccount::query()->where('tenant_id', $tenantId)->count();
        $periodCount = FiscalPeriod::query()->where('tenant_id', $tenantId)->count();
        $ruleCount = AccountingPostRule::query()->where('tenant_id', $tenantId)->count();

        // Gọi lại — không nhân đôi.
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/accounting/setup', ['year' => 2026])->assertOk();
        $this->assertSame($accCount, ChartAccount::query()->where('tenant_id', $tenantId)->count());
        $this->assertSame($periodCount, FiscalPeriod::query()->where('tenant_id', $tenantId)->count());
        $this->assertSame($ruleCount, AccountingPostRule::query()->where('tenant_id', $tenantId)->count());
    }

    public function test_starter_plan_blocks_accounting(): void
    {
        $this->activatePlan(Plan::CODE_STARTER);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/setup/status')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_viewer_cannot_run_setup(): void
    {
        $this->actingAs($this->viewer)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/setup')
            ->assertStatus(403);
    }

    public function test_setup_creates_postable_tk156_subaccounts(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/accounting/setup', ['year' => 2026])->assertOk();
        $tenantId = $this->tenant->getKey();
        // 156 là tổng (not postable), 1561/1562 postable.
        $this->assertFalse((bool) ChartAccount::query()->where('tenant_id', $tenantId)->where('code', '156')->value('is_postable'));
        $this->assertTrue((bool) ChartAccount::query()->where('tenant_id', $tenantId)->where('code', '1561')->value('is_postable'));
        // 131 không có sub → vẫn postable.
        $this->assertTrue((bool) ChartAccount::query()->where('tenant_id', $tenantId)->where('code', '131')->value('is_postable'));
    }

    public function test_tenant_isolation(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/accounting/setup', ['year' => 2026])->assertOk();

        // Tenant B onboard riêng.
        $tenantB = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::create(['name' => 'Other']);
        $owner2 = \CMBcoreSeller\Models\User::factory()->create();
        $tenantB->users()->attach($owner2->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner->value]);
        $this->activatePlanFor($tenantB->getKey(), Plan::CODE_PRO);

        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $tenantB->getKey()])
            ->getJson('/api/v1/accounting/setup/status')
            ->assertStatus(403); // không thuộc tenant B

        $this->actingAs($owner2)->withHeaders(['X-Tenant-Id' => (string) $tenantB->getKey()])
            ->getJson('/api/v1/accounting/setup/status')
            ->assertOk()
            ->assertJsonPath('data.initialized', false);
    }

    private function activatePlanFor(int $tenantId, string $planCode): void
    {
        \CMBcoreSeller\Modules\Billing\Models\Subscription::query()
            ->where('tenant_id', $tenantId)->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        $now = now();
        \CMBcoreSeller\Modules\Billing\Models\Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->getKey(),
            'status' => \CMBcoreSeller\Modules\Billing\Models\Subscription::STATUS_ACTIVE,
            'billing_cycle' => \CMBcoreSeller\Modules\Billing\Models\Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }
}
