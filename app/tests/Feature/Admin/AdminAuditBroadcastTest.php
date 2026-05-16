<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Notifications\BroadcastNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * SPEC 0023 — audit log search + broadcast email.
 */
class AdminAuditBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_search_filters_by_action_and_tenant(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenantA = Tenant::create(['name' => 'A']);
        $tenantB = Tenant::create(['name' => 'B']);

        AuditLog::query()->create(['tenant_id' => $tenantA->id, 'user_id' => $admin->id, 'action' => 'admin.voucher.create', 'changes' => ['code' => 'X1']]);
        AuditLog::query()->create(['tenant_id' => $tenantB->id, 'user_id' => $admin->id, 'action' => 'admin.tenant.suspend', 'changes' => []]);
        AuditLog::query()->create(['tenant_id' => $tenantA->id, 'user_id' => $admin->id, 'action' => 'orders.create', 'changes' => []]);

        // Filter action prefix admin.*
        $this->actingAs($admin)->getJson('/api/v1/admin/audit-logs?action=admin.*')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 2);

        // Filter by tenant
        $this->actingAs($admin)->getJson('/api/v1/admin/audit-logs?tenant_id='.$tenantA->id)
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_audit_log_requires_super_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/v1/admin/audit-logs')->assertStatus(403);
    }

    public function test_broadcast_to_all_owners_dispatches_notifications(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        $staff = User::factory()->create();
        $tenant1 = Tenant::create(['name' => 'T1']);
        $tenant2 = Tenant::create(['name' => 'T2']);
        $tenant1->users()->attach($owner1->getKey(), ['role' => Role::Owner->value]);
        $tenant2->users()->attach($owner2->getKey(), ['role' => Role::Owner->value]);
        $tenant1->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);

        $this->actingAs($admin)->postJson('/api/v1/admin/broadcasts', [
            'subject' => 'Thông báo bảo trì',
            'body_markdown' => '# Thông báo bảo trì\nHệ thống bảo trì lúc 22h.',
            'audience' => ['kind' => 'all_owners'],
        ])->assertCreated()->assertJsonPath('data.recipient_count', 2);

        Notification::assertSentTo($owner1, BroadcastNotification::class);
        Notification::assertSentTo($owner2, BroadcastNotification::class);
        Notification::assertNotSentTo($staff, BroadcastNotification::class);

        $this->assertDatabaseHas('broadcasts', ['subject' => 'Thông báo bảo trì', 'sent_count' => 2]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.broadcast.send']);
    }

    public function test_broadcast_to_tenant_ids_only_sends_to_listed_tenants(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        $tenant1 = Tenant::create(['name' => 'T1']);
        $tenant2 = Tenant::create(['name' => 'T2']);
        $tenant1->users()->attach($owner1->getKey(), ['role' => Role::Owner->value]);
        $tenant2->users()->attach($owner2->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($admin)->postJson('/api/v1/admin/broadcasts', [
            'subject' => 'Riêng cho T1',
            'body_markdown' => 'Thông báo riêng',
            'audience' => ['kind' => 'tenant_ids', 'tenant_ids' => [$tenant1->id]],
        ])->assertCreated()->assertJsonPath('data.recipient_count', 1);

        Notification::assertSentTo($owner1, BroadcastNotification::class);
        Notification::assertNotSentTo($owner2, BroadcastNotification::class);
    }

    public function test_broadcast_to_suspended_tenants_skips_recipients(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Suspended', 'status' => 'suspended']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($admin)->postJson('/api/v1/admin/broadcasts', [
            'subject' => 'Sẽ không tới',
            'body_markdown' => 'Body',
            'audience' => ['kind' => 'all_owners'],
        ])->assertCreated()->assertJsonPath('data.recipient_count', 0);

        Notification::assertNotSentTo($owner, BroadcastNotification::class);
    }
}
