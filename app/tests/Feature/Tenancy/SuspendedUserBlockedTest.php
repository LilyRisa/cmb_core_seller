<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspendedUserBlockedTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_cannot_access_tenant_routes(): void
    {
        $user = User::factory()->create(['suspended_at' => now()]);
        $tenant = Tenant::create(['name' => 'X']);
        $tenant->users()->attach($user->id, ['role' => Role::Owner->value]);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/v1/orders')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'USER_SUSPENDED');
    }

    public function test_active_user_passes(): void
    {
        $user = User::factory()->create(['suspended_at' => null]);
        $tenant = Tenant::create(['name' => 'X']);
        $tenant->users()->attach($user->id, ['role' => Role::Owner->value]);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/v1/orders')
            ->assertOk();
    }
}
