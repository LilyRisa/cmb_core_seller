<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantAcquisitionFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_tenants_by_utm_source(): void
    {
        $admin = AdminUser::factory()->create();
        $fb = Tenant::create(['name' => 'Shop FB']);
        $fb->forceFill(['acquisition' => ['utm_source' => 'facebook', 'utm_campaign' => 'summer']])->save();
        Tenant::create(['name' => 'Shop Direct']);

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/tenants?utm_source=facebook')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Shop FB')
            ->assertJsonPath('data.0.acquisition.utm_source', 'facebook')
            ->assertJsonPath('data.0.acquisition.utm_campaign', 'summer');
    }

    public function test_tenant_without_acquisition_returns_null(): void
    {
        $admin = AdminUser::factory()->create();
        Tenant::create(['name' => 'Shop Direct']);

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/tenants')
            ->assertOk()
            ->assertJsonPath('data.0.acquisition', null);
    }
}
