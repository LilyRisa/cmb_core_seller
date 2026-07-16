<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Billing\Services\VoucherService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AiCreditVoucherTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        Subscription::create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => Plan::query()->where('code', Plan::CODE_PRO)->value('id'),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        Voucher::create([
            'code' => 'AI500', 'name' => 'Tặng 500 lượt AI', 'kind' => Voucher::KIND_AI_CREDITS,
            'value' => 500, 'max_redemptions' => 10, 'is_active' => true,
        ]);
    }

    public function test_redeem_ai_voucher_grants_credits_once_per_tenant(): void
    {
        $tid = (int) $this->tenant->getKey();
        $svc = app(VoucherService::class);

        $result = $svc->redeemGift('AI500', $tid, null);
        $this->assertSame('ai_credits', $result['kind']);
        $this->assertSame(500, $result['granted']);
        $this->assertSame(500, $result['balance']);
        $this->assertSame(500, app(AiCreditService::class)->wallet($tid)->purchased_balance);
        $this->assertSame(1, (int) Voucher::query()->where('code', 'AI500')->value('redemption_count'));

        // Cùng tenant không nhận lại được.
        $this->expectException(HttpResponseException::class);
        $svc->redeemGift('AI500', $tid, null);
    }

    public function test_checkout_only_kind_is_rejected(): void
    {
        Voucher::create(['code' => 'SALE50', 'name' => 'Giảm 50%', 'kind' => Voucher::KIND_PERCENT, 'value' => 50, 'max_redemptions' => -1, 'is_active' => true]);

        $this->expectException(HttpResponseException::class);
        app(VoucherService::class)->redeemGift('SALE50', (int) $this->tenant->getKey(), null);
    }

    public function test_redeem_free_days_voucher_extends_subscription_once_per_tenant(): void
    {
        Voucher::create([
            'code' => 'TANG7NGAY', 'name' => 'Tặng 7 ngày', 'kind' => Voucher::KIND_FREE_DAYS,
            'value' => 7, 'max_redemptions' => -1, 'is_active' => true,
        ]);
        $tid = (int) $this->tenant->getKey();
        $before = Subscription::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tid)->value('current_period_end');

        $result = app(VoucherService::class)->redeemGift('TANG7NGAY', $tid, null);

        $this->assertSame('free_days', $result['kind']);
        $this->assertSame(7, $result['days']);
        $after = Subscription::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tid)->value('current_period_end');
        $this->assertTrue(Carbon::parse($after)->equalTo(Carbon::parse($before)->addDays(7)));

        $this->expectException(HttpResponseException::class);
        app(VoucherService::class)->redeemGift('TANG7NGAY', $tid, null);
    }

    public function test_redeem_plan_upgrade_voucher_swaps_plan(): void
    {
        $starterPlanId = (int) Plan::query()->where('code', Plan::CODE_STARTER)->value('id');
        Voucher::create([
            'code' => 'TANGGOI', 'name' => 'Tặng gói Cơ bản', 'kind' => Voucher::KIND_PLAN_UPGRADE,
            'value' => $starterPlanId, 'max_redemptions' => -1, 'is_active' => true,
            'meta' => ['duration_days' => 15],
        ]);
        $tid = (int) $this->tenant->getKey();

        $result = app(VoucherService::class)->redeemGift('TANGGOI', $tid, null);

        $this->assertSame('plan_upgrade', $result['kind']);
        $this->assertSame(15, $result['days']);
        $this->assertSame(Plan::CODE_STARTER, $result['plan_code']);
        $newSub = Subscription::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tid)
            ->where('status', Subscription::STATUS_TRIALING)->latest('id')->first();
        $this->assertSame(Plan::CODE_STARTER, $newSub?->plan->code);
    }
}
