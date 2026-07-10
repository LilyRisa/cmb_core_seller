<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Services\AdminTenantService;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminSetTestPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_admin_cannot_assign_test_unlimited_plan(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $this->expectException(ValidationException::class);
        app(AdminTenantService::class)->changePlan($tenant, 'test_unlimited', 'monthly', 'Thử gán gói test', 1);
    }

    public function test_admin_can_change_plan_to_monthly_pro(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);

        $subscription = app(AdminTenantService::class)->changePlan(
            $tenant,
            'pro',
            'monthly',
            'Nâng cấp thủ công theo yêu cầu',
            1
        );

        $this->assertSame('pro', $subscription->plan->code);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame(Subscription::CYCLE_MONTHLY, $subscription->billing_cycle);
        $this->assertTrue(
            $subscription->current_period_end->between(now()->addDays(28), now()->addDays(31))
        );
    }
}
