<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_tenant_and_owner_membership(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'tenant_name' => 'Shop A',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'a@example.com')
            ->assertJsonPath('data.tenants.0.role', 'owner')
            ->assertJsonPath('data.tenants.0.name', 'Shop A');

        $this->assertDatabaseHas('users', ['email' => 'a@example.com']);
        $this->assertDatabaseHas('tenants', ['name' => 'Shop A']);
        $this->assertDatabaseHas('tenant_user', ['role' => 'owner']);
    }

    public function test_register_rejects_duplicate_email_and_short_password(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'dup@example.com', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertStatus(422);
    }

    public function test_register_requires_strong_password(): void
    {
        // Thiếu ký tự đặc biệt.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'p1@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertStatus(422);

        // Thiếu chữ số.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'p2@example.com', 'password' => 'Password!!', 'password_confirmation' => 'Password!!',
        ])->assertStatus(422);

        // Thiếu chữ cái.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'p3@example.com', 'password' => '12345678!', 'password_confirmation' => '12345678!',
        ])->assertStatus(422);

        // Đủ điều kiện: ≥8 ký tự + chữ + số + ký tự đặc biệt.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'ok@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertCreated();
    }

    public function test_update_profile_password_requires_strong_policy(): void
    {
        [$user] = $this->userWithTenant();
        $user->forceFill(['password' => Hash::make('Current-pass-1')])->save();

        // Mật khẩu mới yếu (chỉ đủ dài) phải bị từ chối — đồng bộ chuẩn /auth/register.
        $this->actingAs($user)->patchJson('/api/v1/auth/profile', [
            'current_password' => 'Current-pass-1',
            'password' => 'weakpassword',
            'password_confirmation' => 'weakpassword',
        ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');

        // Mật khẩu đủ mạnh (≥8 + hoa + thường + số + ký tự đặc biệt) được chấp nhận.
        $this->actingAs($user)->patchJson('/api/v1/auth/profile', [
            'current_password' => 'Current-pass-1',
            'password' => 'New-strong-99',
            'password_confirmation' => 'New-strong-99',
        ])->assertOk();

        $this->assertTrue(Hash::check('New-strong-99', $user->fresh()->password));
    }

    public function test_login_with_valid_and_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'b@example.com', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'b@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.email', 'b@example.com');

        $this->postJson('/api/v1/auth/login', ['email' => 'b@example.com', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_user_with_tenants(): void
    {
        [$user, $tenant] = $this->userWithTenant(Role::Owner);

        $this->actingAs($user)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->getKey())
            ->assertJsonPath('data.tenants.0.id', $tenant->getKey());
    }

    public function test_tenant_endpoint_requires_tenant_header(): void
    {
        [$user] = $this->userWithTenant();

        $this->actingAs($user)->getJson('/api/v1/tenant')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'TENANT_REQUIRED');
    }

    public function test_tenant_endpoint_forbids_non_member(): void
    {
        [$user] = $this->userWithTenant();
        $otherTenant = Tenant::create(['name' => 'Other']);

        $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $otherTenant->getKey())
            ->getJson('/api/v1/tenant')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'TENANT_FORBIDDEN');
    }

    public function test_tenant_show_returns_current_tenant_and_role(): void
    {
        [$user, $tenant] = $this->userWithTenant(Role::Admin);

        $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/v1/tenant')
            ->assertOk()
            ->assertJsonPath('data.id', $tenant->getKey())
            ->assertJsonPath('data.current_role', 'admin');
    }

    public function test_member_management_respects_role(): void
    {
        [$owner, $tenant] = $this->userWithTenant(Role::Owner);
        $viewer = User::factory()->create();
        $tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);

        // Owner can list members.
        $this->actingAs($owner)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/v1/tenant/members')->assertOk()
            ->assertJsonCount(2, 'data');

        // Viewer cannot.
        $this->actingAs($viewer)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/v1/tenant/members')->assertForbidden();

        // Owner adds an existing user as staff_order.
        $newcomer = User::factory()->create(['email' => 'new@example.com']);
        $this->actingAs($owner)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson('/api/v1/tenant/members', ['email' => 'new@example.com', 'role' => 'staff_order'])
            ->assertCreated()->assertJsonPath('data.role', 'staff_order');

        $this->assertDatabaseHas('tenant_user', ['user_id' => $newcomer->getKey(), 'role' => 'staff_order']);

        // Adding a non-existent user => 422.
        $this->actingAs($owner)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson('/api/v1/tenant/members', ['email' => 'ghost@example.com', 'role' => 'viewer'])
            ->assertStatus(422)->assertJsonPath('error.code', 'USER_NOT_FOUND');
    }

    /**
     * @return array{0: User, 1: Tenant}
     */
    protected function userWithTenant(Role $role = Role::Owner): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop '.uniqid()]);
        $tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return [$user, $tenant];
    }
}
