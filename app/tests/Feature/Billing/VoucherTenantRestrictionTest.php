<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Billing\Services\VoucherService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tests\TestCase;

/** Voucher giới hạn theo `valid_tenant_ids` — chỉ tenant nằm trong danh sách mới redeem được. */
class VoucherTenantRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $allowedTenant;

    private Tenant $otherTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->allowedTenant = Tenant::create(['name' => 'Shop A']);
        $this->otherTenant = Tenant::create(['name' => 'Shop B']);
        foreach ([$this->allowedTenant, $this->otherTenant] as $t) {
            Subscription::create([
                'tenant_id' => $t->getKey(),
                'plan_id' => Plan::query()->where('code', Plan::CODE_PRO)->value('id'),
                'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
                'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
            ]);
        }
    }

    public function test_tenant_outside_allow_list_is_rejected(): void
    {
        Voucher::create([
            'code' => 'VIPONLY', 'name' => 'Tặng VIP', 'kind' => Voucher::KIND_AI_CREDITS,
            'value' => 100, 'max_redemptions' => -1, 'is_active' => true,
            'valid_tenant_ids' => [$this->allowedTenant->getKey()],
        ]);

        $this->expectException(HttpResponseException::class);
        app(VoucherService::class)->redeemGift('VIPONLY', (int) $this->otherTenant->getKey(), null);
    }

    public function test_tenant_inside_allow_list_succeeds(): void
    {
        Voucher::create([
            'code' => 'VIPONLY', 'name' => 'Tặng VIP', 'kind' => Voucher::KIND_AI_CREDITS,
            'value' => 100, 'max_redemptions' => -1, 'is_active' => true,
            'valid_tenant_ids' => [$this->allowedTenant->getKey()],
        ]);

        $result = app(VoucherService::class)->redeemGift('VIPONLY', (int) $this->allowedTenant->getKey(), null);

        $this->assertSame(100, $result['granted']);
    }

    public function test_empty_allow_list_means_every_tenant(): void
    {
        Voucher::create([
            'code' => 'ANYONE', 'name' => 'Tặng ai cũng được', 'kind' => Voucher::KIND_AI_CREDITS,
            'value' => 50, 'max_redemptions' => -1, 'is_active' => true,
            'valid_tenant_ids' => null,
        ]);

        $result = app(VoucherService::class)->redeemGift('ANYONE', (int) $this->otherTenant->getKey(), null);

        $this->assertSame(50, $result['granted']);
    }
}
