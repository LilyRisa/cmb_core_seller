<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
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

        $tenantId = Tenant::where('name', 'Trial Shop')->value('id');
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

        // SPEC 0032 — 3 gói: trial (3 gian hàng, 1/nền tảng), starter (90k, 2/nền tảng), pro (170k, không giới hạn).
        $this->assertSame(3, $plans[Plan::CODE_TRIAL]->maxChannelAccounts());
        $this->assertSame(1, $plans[Plan::CODE_TRIAL]->maxChannelAccountsPerPlatform());
        $this->assertSame(2, $plans[Plan::CODE_STARTER]->maxChannelAccountsPerPlatform());
        $this->assertSame(-1, $plans[Plan::CODE_PRO]->maxChannelAccounts());
        $this->assertSame(-1, $plans[Plan::CODE_PRO]->maxChannelAccountsPerPlatform());

        // Trial: không tính năng nào.
        $this->assertFalse($plans[Plan::CODE_TRIAL]->hasFeature('messaging_inbox'));
        // Starter (90k): chỉ mở nhắn tin Facebook Page — KHÔNG AI / kế toán / marketing.
        $this->assertTrue($plans[Plan::CODE_STARTER]->hasFeature('messaging_inbox'));
        $this->assertFalse($plans[Plan::CODE_STARTER]->hasFeature('messaging_ai'));
        $this->assertFalse($plans[Plan::CODE_STARTER]->hasFeature('accounting_basic'));
        $this->assertFalse($plans[Plan::CODE_STARTER]->hasFeature('marketing_facebook'));
        // Pro (170k): full + AI.
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('messaging_ai'));
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('marketing_facebook'));
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('accounting_advanced'));
        $this->assertTrue($plans[Plan::CODE_PRO]->hasFeature('ai'));
        $this->assertSame(500, $plans[Plan::CODE_PRO]->aiCreditsMonthly());

        // Giá: VND đồng (bigint).
        $this->assertSame(90_000, (int) $plans[Plan::CODE_STARTER]->price_monthly);
        $this->assertSame(170_000, (int) $plans[Plan::CODE_PRO]->price_monthly);
        $this->assertSame(900_000, (int) $plans[Plan::CODE_STARTER]->price_yearly);
        $this->assertSame(1_700_000, (int) $plans[Plan::CODE_PRO]->price_yearly);
    }
}
