<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuardEnforcedTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_request_returns_401_on_admin_route(): void
    {
        $this->getJson('/api/v1/admin/tenants')->assertStatus(401);
    }

    public function test_regular_user_session_cannot_access_admin_route(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        $this->getJson('/api/v1/admin/tenants')->assertStatus(401);
    }

    public function test_admin_session_can_access_admin_route(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web');
        $this->getJson('/api/v1/admin/tenants')->assertOk();
    }
}
