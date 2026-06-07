<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Messaging\Support\MessagingChannelAuthorizer;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADR-0021 — authz private channel `tenant.{id}.messaging`. Bảo vệ: chỉ thành viên
 * tenant + có quyền messaging.view mới nghe được realtime; cross-tenant phải bị chặn
 * (sai = lộ tin nhắn tenant khác).
 */
class MessagingChannelAuthzTest extends TestCase
{
    use RefreshDatabase;

    private function authorizer(): MessagingChannelAuthorizer
    {
        return app(MessagingChannelAuthorizer::class);
    }

    private function memberOf(Tenant $tenant, Role $role = Role::Owner): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    public function test_member_with_view_permission_can_listen(): void
    {
        $tenant = Tenant::create(['name' => 'A']);
        $user = $this->memberOf($tenant, Role::Owner);

        $this->assertTrue($this->authorizer()->canViewTenantMessaging($user, (int) $tenant->getKey()));
    }

    public function test_non_member_cannot_listen(): void
    {
        $tenant = Tenant::create(['name' => 'A']);
        $outsider = User::factory()->create(['email_verified_at' => now()]);

        $this->assertFalse($this->authorizer()->canViewTenantMessaging($outsider, (int) $tenant->getKey()));
    }

    public function test_member_of_other_tenant_cannot_listen_to_this_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'A']);
        $tenantB = Tenant::create(['name' => 'B']);
        $userB = $this->memberOf($tenantB, Role::Owner);

        // userB thuộc tenant B, KHÔNG được nghe channel của tenant A.
        $this->assertFalse($this->authorizer()->canViewTenantMessaging($userB, (int) $tenantA->getKey()));
    }

    public function test_unknown_tenant_returns_false(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->assertFalse($this->authorizer()->canViewTenantMessaging($user, 999999));
    }
}
