<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
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
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend")
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
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend")
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
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reactivate")->assertOk();
        $this->assertTrue($other->fresh()->is_active);
    }
}
