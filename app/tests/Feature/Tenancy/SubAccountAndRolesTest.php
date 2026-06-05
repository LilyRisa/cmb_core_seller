<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantRole;
use CMBcoreSeller\Modules\Tenancy\Services\TenantRoleProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubAccountAndRolesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Tenant, 2: array<string, TenantRole>} */
    private function ownerWithTenant(): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'Shop X']);
        $roles = app(TenantRoleProvisioner::class)->seedDefaults($tenant);
        $tenant->users()->attach($owner->getKey(), ['role' => 'owner', 'role_id' => $roles['owner']->getKey()]);

        return [$owner, $tenant, $roles];
    }

    private function h(Tenant $tenant): array
    {
        return ['X-Tenant-Id' => (string) $tenant->getKey()];
    }

    public function test_seeded_tenant_has_a_5_char_code_and_owner_role(): void
    {
        [, $tenant, $roles] = $this->ownerWithTenant();

        $this->assertMatchesRegularExpression('/^[a-z0-9]{5}$/', (string) $tenant->code);
        $this->assertTrue($roles['owner']->is_owner);
    }

    public function test_permission_catalog_is_readable_by_members(): void
    {
        [$owner, $tenant] = $this->ownerWithTenant();

        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->getJson('/api/v1/tenant/permissions')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'dashboard');
    }

    public function test_owner_creates_custom_role_rejecting_owner_only_permissions(): void
    {
        [$owner, $tenant] = $this->ownerWithTenant();

        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/roles', ['name' => 'Thu ngân', 'permissions' => ['orders.view', 'orders.create']])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Thu ngân')
            ->assertJsonPath('data.is_system', false);

        // Owner-only permission is rejected at the API.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/roles', ['name' => 'Bậy', 'permissions' => ['billing.manage']])
            ->assertStatus(422);

        // Unknown permission is rejected.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/roles', ['name' => 'Bậy2', 'permissions' => ['orders.nuke']])
            ->assertStatus(422);
    }

    public function test_sub_account_is_created_and_logs_in_by_username(): void
    {
        [$owner, $tenant] = $this->ownerWithTenant();
        $roleId = (int) $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/roles', ['name' => 'Nhân viên', 'permissions' => ['orders.view']])
            ->json('data.id');

        $res = $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/members', ['mode' => 'sub', 'name' => 'Thu', 'password' => 'matkhau1', 'role_id' => $roleId])
            ->assertCreated()
            ->assertJsonPath('data.is_sub_account', true);

        $username = $res->json('data.username');
        $this->assertSame('thu@'.$tenant->code, $username);

        // The sub-account has no email and is pre-verified.
        $sub = User::where('username', $username)->firstOrFail();
        $this->assertNull($sub->email);
        $this->assertNotNull($sub->email_verified_at);

        // Mobile token login by username succeeds (email-less sub-account).
        $token = $this->postJson('/api/v1/auth/token', [
            'login' => $username, 'password' => 'matkhau1', 'device_name' => 'Android',
        ])->assertCreated()->json('data.token');
        $this->assertNotEmpty($token);

        // /me for the sub-account reflects exactly the assigned permissions.
        $sub = User::where('username', $username)->firstOrFail();
        $this->actingAs($sub)->getJson('/api/v1/auth/me')->assertOk()
            ->assertJsonPath('data.tenants.0.permissions', ['orders.view'])
            ->assertJsonPath('data.username', $username);
    }

    public function test_role_enforced_at_api_for_sub_account(): void
    {
        [$owner, $tenant] = $this->ownerWithTenant();
        $roleId = (int) $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/roles', ['name' => 'Xem đơn', 'permissions' => ['orders.view']])
            ->json('data.id');
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/members', ['mode' => 'sub', 'name' => 'Nam', 'password' => 'matkhau1', 'role_id' => $roleId]);

        $sub = User::where('username', 'nam@'.$tenant->code)->firstOrFail();

        // Lacks team.manage ⇒ cannot view roles/members.
        $this->actingAs($sub)->withHeaders($this->h($tenant))
            ->getJson('/api/v1/tenant/roles')->assertForbidden();
        $this->actingAs($sub)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/roles', ['name' => 'X', 'permissions' => []])->assertForbidden();
    }

    public function test_owner_bypasses_every_permission(): void
    {
        [$owner, $tenant, $roles] = $this->ownerWithTenant();

        $this->assertTrue($roles['owner']->grants('anything.at.all'));
        $this->assertTrue($roles['owner']->grants('billing.manage'));

        // Owner can reach a manager endpoint.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->getJson('/api/v1/tenant/roles')->assertOk();
    }

    public function test_role_update_and_delete_guards(): void
    {
        [$owner, $tenant, $roles] = $this->ownerWithTenant();
        $custom = (int) $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/roles', ['name' => 'Tạm', 'permissions' => ['orders.view']])
            ->json('data.id');

        // Update permissions.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->putJson("/api/v1/tenant/roles/{$custom}", ['name' => 'Tạm', 'permissions' => ['orders.view', 'orders.update']])
            ->assertOk()->assertJsonPath('data.permissions', ['orders.view', 'orders.update']);

        // Owner role cannot be edited or deleted.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->putJson('/api/v1/tenant/roles/'.$roles['owner']->getKey(), ['name' => 'X', 'permissions' => []])
            ->assertForbidden();
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->deleteJson('/api/v1/tenant/roles/'.$roles['owner']->getKey())
            ->assertForbidden();

        // A role in use cannot be deleted until reassigned.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/members', ['mode' => 'sub', 'name' => 'An', 'password' => 'matkhau1', 'role_id' => $custom]);
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->deleteJson("/api/v1/tenant/roles/{$custom}")->assertStatus(409);
    }

    public function test_cannot_assign_or_remove_owner_role_via_members(): void
    {
        [$owner, $tenant, $roles] = $this->ownerWithTenant();
        $ownerRoleId = $roles['owner']->getKey();

        // Assigning the owner role to a new sub-account is rejected.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->postJson('/api/v1/tenant/members', ['mode' => 'sub', 'name' => 'Boss', 'password' => 'matkhau1', 'role_id' => $ownerRoleId])
            ->assertStatus(422);

        // The owner member cannot be removed.
        $this->actingAs($owner)->withHeaders($this->h($tenant))
            ->deleteJson('/api/v1/tenant/members/'.$owner->getKey())
            ->assertForbidden();
    }
}
