<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Billing\Services\VoucherService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
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

        $result = $svc->redeemAiCredits('AI500', $tid, null);
        $this->assertSame(500, $result['granted']);
        $this->assertSame(500, app(AiCreditService::class)->wallet($tid)->purchased_balance);
        $this->assertSame(1, (int) Voucher::query()->where('code', 'AI500')->value('redemption_count'));

        // Cùng tenant không nhận lại được.
        $this->expectException(HttpResponseException::class);
        $svc->redeemAiCredits('AI500', $tid, null);
    }

    public function test_wrong_kind_is_rejected(): void
    {
        Voucher::create(['code' => 'SALE50', 'name' => 'Giảm 50%', 'kind' => Voucher::KIND_PERCENT, 'value' => 50, 'max_redemptions' => -1, 'is_active' => true]);

        $this->expectException(HttpResponseException::class);
        app(VoucherService::class)->redeemAiCredits('SALE50', (int) $this->tenant->getKey(), null);
    }
}
