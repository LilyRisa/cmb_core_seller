<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6.4 — SPEC 0018 §3.1: tenant tạo ⇒ trial 14 ngày tự bật.
 */
class BillingTrialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_register_creates_tenant_with_trial_subscription(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Trial User',
            'email' => 'trial@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Trial Shop',
        ])->assertCreated();

        $tenantId = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::where('name', 'Trial Shop')->value('id');
        $this->assertNotNull($tenantId);

        // Bỏ TenantScope: test không có current tenant nên global scope sẽ filter sạch.
        $sub = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->with('plan')->first();
        $this->assertNotNull($sub, 'Trial subscription phải được tự tạo khi register.');
        $this->assertSame(Subscription::STATUS_TRIALING, $sub->status);
        $this->assertSame(Subscription::CYCLE_TRIAL, $sub->billing_cycle);
        $this->assertNotNull($sub->trial_ends_at);
        $this->assertTrue($sub->trial_ends_at->isFuture());
        $this->assertSame(Plan::CODE_TRIAL, $sub->plan->code);
        // 14 ngày trial (default).
        $this->assertEqualsWithDelta(14, (int) now()->diffInDays($sub->trial_ends_at, false), 1);
    }

    public function test_plans_seeded_with_correct_limits_and_features(): void
    {
        $plans = Plan::query()->orderBy('sort_order')->get()->keyBy('code');

        $this->assertSame(2, $plans[Plan::CODE_TRIAL]->maxChannelAccounts());
        $this->assertSame(2, $plans[Plan::CODE_STARTER]->maxChannelAccounts());
        $this->assertSame(5, $plans[Plan::CODE_PRO]->maxChannelAccounts());
        $this->assertSame(10, $plans[Plan::CODE_BUSINESS]->maxChannelAccounts());

        // Starter chỉ tính năng cơ bản.
        $this->assertFalse($plans[Plan::CODE_STARTER]->hasFeature('finance_settlements'));
        $this->assertFalse($plans[Plan::CODE_STARTER]->hasFeature('procurement'));
        // Pro mở khoá nâng cao.
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('finance_settlements'));
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('procurement'));
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('fifo_cogs'));
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('demand_planning'));
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('profit_reports'));
        $this->assertFalse($plans[Plan::CODE_PRO]->hasFeature('mass_listing'));
        // Business mở khoá tất cả.
        $this->assertTrue($plans[Plan::CODE_BUSINESS]->hasFeature('mass_listing'));
        $this->assertTrue($plans[Plan::CODE_BUSINESS]->hasFeature('automation_rules'));

        // Giá: VND đồng (bigint).
        $this->assertSame(99_000, (int) $plans[Plan::CODE_STARTER]->price_monthly);
        $this->assertSame(199_000, (int) $plans[Plan::CODE_PRO]->price_monthly);
        $this->assertSame(399_000, (int) $plans[Plan::CODE_BUSINESS]->price_monthly);
        // Yearly = giá 10 tháng (tặng 2 tháng).
        $this->assertSame(99_000 * 10, (int) $plans[Plan::CODE_STARTER]->price_yearly);
        $this->assertSame(199_000 * 10, (int) $plans[Plan::CODE_PRO]->price_yearly);
        $this->assertSame(399_000 * 10, (int) $plans[Plan::CODE_BUSINESS]->price_yearly);
    }
}
