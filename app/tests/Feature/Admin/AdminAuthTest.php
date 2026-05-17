<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec 2026-05-17 — chỉ admin login (guard `admin_web`) đi qua /api/v1/admin/*.
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/admin/tenants')
            ->assertStatus(401);

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(401);
    }

    public function test_super_admin_can_list_tenants(): void
    {
        $admin = AdminUser::factory()->create();
        $other = User::factory()->create();
        $tenant = Tenant::create(['name' => 'TestShop']);
        $tenant->users()->attach($other->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/tenants')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'TestShop');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/admin/tenants')->assertStatus(401);
    }

    public function test_super_admin_routes_dont_need_tenant_header(): void
    {
        $admin = AdminUser::factory()->create();

        // KHÔNG truyền X-Tenant-Id — admin global.
        $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/users')
            ->assertOk();
    }
}
