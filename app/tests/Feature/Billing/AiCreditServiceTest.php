<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Exceptions\AiCreditException;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
    }

    private function subscribe(string $code, string $status = Subscription::STATUS_ACTIVE): void
    {
        Subscription::query()->withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => Plan::query()->where('code', $code)->value('id'),
            'status' => $status,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    private function svc(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    public function test_pro_plan_grants_monthly_allowance_then_exhausts(): void
    {
        $this->subscribe(Plan::CODE_PRO);
        $tid = (int) $this->tenant->getKey();

        $this->assertTrue($this->svc()->aiEnabled($tid));
        $this->assertSame(500, $this->svc()->monthlyAllowance($tid));
        $this->assertSame(500, $this->svc()->available($tid));

        $this->svc()->consume($tid, 500);
        $this->assertSame(0, $this->svc()->available($tid));
        $this->assertFalse($this->svc()->canUse($tid));

        $this->expectException(AiCreditException::class);
        $this->svc()->consume($tid, 1);
    }

    public function test_purchased_credits_used_after_allowance_and_cap_5000(): void
    {
        $this->subscribe(Plan::CODE_PRO);
        $tid = (int) $this->tenant->getKey();

        // Mua 5000 (cộng dồn tối đa 5000); mua thêm không vượt trần.
        $this->assertSame(5000, $this->svc()->grantPurchase($tid, 5000));
        $this->assertSame(0, $this->svc()->grantPurchase($tid, 600));

        // Dùng hết 500 tặng + còn 5000 mua.
        $this->svc()->consume($tid, 500);
        $this->assertSame(5000, $this->svc()->available($tid));
        $this->svc()->consume($tid, 200);
        $this->assertSame(4800, $this->svc()->available($tid));
    }

    public function test_downgrade_blocks_ai_but_keeps_purchased_credits(): void
    {
        $this->subscribe(Plan::CODE_PRO);
        $tid = (int) $this->tenant->getKey();
        $this->svc()->grantPurchase($tid, 1000);

        // Hạ về trial (không có feature ai).
        $this->subscribe(Plan::CODE_TRIAL);

        $this->assertFalse($this->svc()->aiEnabled($tid));
        $this->assertFalse($this->svc()->canUse($tid));
        try {
            $this->svc()->consume($tid, 1);
            $this->fail('Phải ném AiCreditException khi gói không có AI.');
        } catch (AiCreditException $e) {
            $this->assertSame('AI_UNAVAILABLE', $e->errorCode);
        }

        // Credit mua vẫn được giữ — nâng lại pro thì dùng được.
        $this->subscribe(Plan::CODE_PRO);
        $this->assertSame(500 + 1000, $this->svc()->available($tid));
    }

    public function test_unlimited_test_plan_never_consumes(): void
    {
        $plan = Plan::query()->create([
            'code' => 'internal_unlimited_ai', 'name' => 'Internal', 'description' => '',
            'is_active' => false, 'sort_order' => 98,
            'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'VND', 'trial_days' => 0,
            'limits' => ['ai_credits_monthly' => -1], 'features' => ['ai' => true],
        ]);
        $this->subscribe($plan->code);
        $tid = (int) $this->tenant->getKey();

        $this->assertTrue($this->svc()->unlimited($tid));
        $this->assertTrue($this->svc()->canUse($tid, 999999));
        $this->svc()->consume($tid, 999999); // no-op, không ném
        $this->assertSame(0, $this->svc()->wallet($tid)->period_used);
    }
}
