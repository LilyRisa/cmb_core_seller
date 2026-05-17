<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_succeeds(): void
    {
        AdminUser::factory()->create([
            'username' => 'ops_a', 'password' => 'pa$$word1',
        ]);

        $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'ops_a', 'password' => 'pa$$word1',
        ])->assertOk()->assertJsonPath('data.username', 'ops_a');
    }

    public function test_login_wrong_password_returns_401(): void
    {
        AdminUser::factory()->create(['username' => 'ops_b', 'password' => 'right']);
        $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'ops_b', 'password' => 'wrong',
        ])->assertStatus(401)->assertJsonPath('error.code', 'ADMIN_AUTH_FAILED');
    }

    public function test_login_inactive_admin_returns_401(): void
    {
        AdminUser::factory()->inactive()->create(['username' => 'ops_c', 'password' => 'p1']);
        $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'ops_c', 'password' => 'p1',
        ])->assertStatus(401)->assertJsonPath('error.code', 'ADMIN_AUTH_FAILED');
    }

    public function test_me_returns_admin_after_login(): void
    {
        $a = AdminUser::factory()->create(['username' => 'ops_d', 'password' => 'p']);
        $this->actingAs($a, 'admin_web');
        $this->getJson('/api/v1/admin/auth/me')->assertOk()->assertJsonPath('data.username', 'ops_d');
    }

    public function test_logout_writes_audit_and_returns_ok(): void
    {
        $a = AdminUser::factory()->create();
        $this->actingAs($a, 'admin_web');

        $this->postJson('/api/v1/admin/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.auth.logout',
            'admin_user_id' => $a->id,
        ]);
    }

    public function test_logout_requires_auth(): void
    {
        // No actingAs — should be rejected by middleware auth:admin.
        $this->postJson('/api/v1/admin/auth/logout')->assertStatus(401);
    }

    public function test_change_password_requires_current(): void
    {
        $a = AdminUser::factory()->create(['password' => 'oldpwd123']);
        $this->actingAs($a, 'admin_web');

        $this->postJson('/api/v1/admin/auth/change-password', [
            'current_password' => 'wrong',
            'password' => 'newpwd123',
        ])->assertStatus(401)->assertJsonPath('error.code', 'ADMIN_AUTH_FAILED');

        $this->postJson('/api/v1/admin/auth/change-password', [
            'current_password' => 'oldpwd123',
            'password' => 'newpwd123',
        ])->assertOk();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpwd123', $a->fresh()->password));
    }
}
