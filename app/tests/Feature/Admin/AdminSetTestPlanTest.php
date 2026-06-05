<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Services\AdminTenantService;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSetTestPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->seed(TestUnlimitedPlanSeeder::class);
    }

    public function test_manually_setting_test_plan_gives_a_long_unlimited_period(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);

        $sub = app(AdminTenantService::class)->changePlan(
            $tenant, 'test_unlimited', 'monthly', 'Cấp gói test để kiểm thử', 1,
        );

        $this->assertSame('test_unlimited', $sub->plan->code);
        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);
        // Không hết hạn trong vài năm tới (gói test = không giới hạn).
        $this->assertTrue($sub->current_period_end->greaterThan(now()->addYears(10)));
        // AI không giới hạn trên gói test.
        $this->assertTrue(app(AiCreditService::class)->unlimited((int) $tenant->getKey()));
    }

    public function test_manually_setting_paid_plan_uses_normal_period(): void
    {
        $tenant = Tenant::create(['name' => 'Shop 2']);

        $sub = app(AdminTenantService::class)->changePlan($tenant, 'pro', 'monthly', 'Nâng gói cho khách', 1);

        $this->assertSame('pro', $sub->plan->code);
        $this->assertTrue($sub->current_period_end->lessThan(now()->addDays(40)));
    }
}
