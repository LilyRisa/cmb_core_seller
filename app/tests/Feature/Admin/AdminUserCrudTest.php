<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web');

        return $admin;
    }

    public function test_list_returns_paginated_admins(): void
    {
        $this->actingAdmin();
        AdminUser::factory()->count(3)->create();

        $r = $this->getJson('/api/v1/admin/admin-users')->assertOk();
        $this->assertGreaterThanOrEqual(4, count($r->json('data')));
        $this->assertArrayHasKey('pagination', $r->json('meta'));
    }

    public function test_list_search_filters_by_q(): void
    {
        $this->actingAdmin();
        AdminUser::factory()->create(['username' => 'needle_a']);
        AdminUser::factory()->create(['username' => 'needle_b']);
        AdminUser::factory()->create(['username' => 'unrelated']);

        $r = $this->getJson('/api/v1/admin/admin-users?q=needle')->assertOk();
        $names = collect($r->json('data'))->pluck('username')->all();
        $this->assertContains('needle_a', $names);
        $this->assertContains('needle_b', $names);
        $this->assertNotContains('unrelated', $names);
    }

    public function test_create_admin(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/admin-users', [
            'username' => 'newb', 'name' => 'New B', 'password' => 'pw123456',
        ])->assertCreated()->assertJsonPath('data.username', 'newb');
        $this->assertDatabaseHas('admin_users', ['username' => 'newb']);
    }

    public function test_create_rejects_invalid_username(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/admin-users', [
            'username' => 'BAD UPPER', 'name' => 'X', 'password' => 'pw123456',
        ])->assertStatus(422);
    }

    public function test_show_returns_admin(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->create();
        $this->getJson("/api/v1/admin/admin-users/{$other->id}")
            ->assertOk()->assertJsonPath('data.id', $other->id);
    }

    public function test_update_admin_metadata(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->create();
        $this->patchJson("/api/v1/admin/admin-users/{$other->id}", ['name' => 'Renamed'])
            ->assertOk()->assertJsonPath('data.name', 'Renamed');
    }

    public function test_cannot_suspend_self(): void
    {
        $me = $this->actingAdmin();
        $this->postJson("/api/v1/admin/admin-users/{$me->id}/suspend")
            ->assertStatus(409)->assertJsonPath('error.code', 'CANNOT_SELF_MUTATE');
    }

    public function test_cannot_reset_password_self(): void
    {
        $me = $this->actingAdmin();
        $this->postJson("/api/v1/admin/admin-users/{$me->id}/reset-password", ['password' => 'pw12345678'])
            ->assertStatus(409)->assertJsonPath('error.code', 'CANNOT_SELF_MUTATE');
    }

    public function test_suspend_works_for_other_admin(): void
    {
        $this->actingAdmin();
        AdminUser::factory()->create();
        $target = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'Vi phạm điều khoản sử dụng dịch vụ.'])
            ->assertOk()->assertJsonPath('data.is_active', false);
    }

    public function test_cannot_suspend_last_active_admin(): void
    {
        // Scenario: acting admin's session is still alive but their is_active was flipped
        // off in another window (admin A suspended admin B who happens to be the actor).
        // Now actor (inactive but session-authed) is the LAST active... no — they're not active.
        // Realistic case: actor is active; target is the last OTHER active admin while the rest are inactive.
        // After suspending target, only the actor remains. But the check counts target's is_active=true →
        // count <= 1 means target IS the only active one. That requires actor.is_active=false, which is
        // impossible if we trust middleware. So we set the actor's row to is_active=false directly to
        // simulate a session that survived a remote suspend (rare but real):
        $me = $this->actingAdmin();
        $target = AdminUser::factory()->create();
        // Deactivate $me in DB while session stays.
        $me->forceFill(['is_active' => false])->save();
        // Now only $target is active. Acting as $me (session), suspend $target → LAST_ACTIVE_ADMIN.
        // Reason validation runs before the LAST_ACTIVE_ADMIN business check, so a valid reason
        // must be sent for this test to actually exercise that check (not just fail on 422).
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'Test lý do đủ dài.'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'LAST_ACTIVE_ADMIN');
    }

    public function test_reset_password(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reset-password", ['password' => 'newpwd99'])
            ->assertOk();
        $this->assertTrue(Hash::check('newpwd99', $other->fresh()->password));
    }

    public function test_reactivate(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->inactive()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reactivate", ['reason' => 'Khách yêu cầu mở lại tài khoản admin.'])
            ->assertOk();
        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->actingAdmin();
        $target = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'ngắn'])
            ->assertStatus(422);
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend")
            ->assertStatus(422);
    }

    public function test_reactivate_requires_reason(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->inactive()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reactivate")
            ->assertStatus(422);
    }

    public function test_suspend_writes_reason_to_audit_log(): void
    {
        $this->actingAdmin();
        $target = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'Vi phạm điều khoản sử dụng dịch vụ.'])
            ->assertOk();

        $log = AuditLog::query()
            ->where('action', 'admin.admin_user.suspend')
            ->where('auditable_id', $target->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('Vi phạm điều khoản sử dụng dịch vụ.', $log->changes['reason'] ?? null);
    }
}
