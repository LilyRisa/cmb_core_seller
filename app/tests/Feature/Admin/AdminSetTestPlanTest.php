<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Services\AdminTenantService;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
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
}
