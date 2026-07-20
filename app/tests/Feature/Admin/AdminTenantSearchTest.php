<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/v1/admin/tenants?q= dùng cho TenantPicker (admin chọn tenant theo mã/tên/email
 * thay vì gõ ID số — giao diện không hiển thị ID). Trước đây `q` chỉ khớp name/slug.
 */
class AdminTenantSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_tenant_code(): void
    {
        $admin = AdminUser::factory()->create();
        Tenant::create(['name' => 'Shop Alpha', 'code' => 'alpha1']);
        Tenant::create(['name' => 'Shop Beta', 'code' => 'beta2']);

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/tenants?q=alpha1')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Shop Alpha')
            ->assertJsonPath('data.0.code', 'alpha1');
    }

    public function test_search_matches_owner_email(): void
    {
        $admin = AdminUser::factory()->create();
        $owner = User::factory()->create(['email' => 'owner-x@example.com']);
        $tenant = Tenant::create(['name' => 'Shop Gamma']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        Tenant::create(['name' => 'Shop Delta']);

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/tenants?q=owner-x@example.com')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Shop Gamma');
    }

    public function test_search_by_name_still_works(): void
    {
        $admin = AdminUser::factory()->create();
        Tenant::create(['name' => 'Unique Name Co']);
        Tenant::create(['name' => 'Other Shop']);

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/tenants?q=Unique Name')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1);
    }
}
