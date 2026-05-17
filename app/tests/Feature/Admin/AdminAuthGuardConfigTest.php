<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminAuthGuardConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_web_guard_resolves_admin_user(): void
    {
        $admin = AdminUser::factory()->create();
        Auth::guard('admin_web')->login($admin);
        $this->assertTrue(Auth::guard('admin_web')->check());
        $this->assertSame($admin->id, Auth::guard('admin_web')->user()->id);
    }

    public function test_admin_guard_uses_admin_users_provider(): void
    {
        $cfg = config('auth.guards.admin');
        $this->assertSame('sanctum', $cfg['driver']);
        $this->assertSame('admin_users', $cfg['provider']);
    }

    public function test_sanctum_guard_array_includes_admin_web(): void
    {
        $this->assertContains('admin_web', config('sanctum.guard'));
    }
}
