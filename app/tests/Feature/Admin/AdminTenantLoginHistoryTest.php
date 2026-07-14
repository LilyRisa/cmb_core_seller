<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\UserLoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantLoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_login_history_for_tenant_members_only(): void
    {
        $admin = AdminUser::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $member = User::factory()->create(['name' => 'NV A']);
        $tenant->users()->attach($member->getKey(), ['role' => Role::StaffOrder->value]);
        $outsider = User::factory()->create();

        UserLoginEvent::query()->create(['user_id' => $member->getKey(), 'ip_address' => '1.2.3.4', 'logged_in_at' => now()]);
        UserLoginEvent::query()->create(['user_id' => $outsider->getKey(), 'ip_address' => '9.9.9.9', 'logged_in_at' => now()]);

        $res = $this->actingAs($admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$tenant->getKey()}/login-history")
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('NV A', $rows[0]['name']);
    }
}
