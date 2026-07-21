<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantUserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function bootstrap(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    public function test_show_returns_user_with_tenants(): void
    {
        $this->bootstrap();
        $u = User::factory()->create(['email' => 'shop@x.vn']);
        $t = Tenant::create(['name' => 'X']);
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);

        $r = $this->getJson("/api/v1/admin/users/{$u->id}")->assertOk();
        $this->assertSame($u->id, $r->json('data.id'));
        $this->assertSame('shop@x.vn', $r->json('data.email'));
        $this->assertCount(1, $r->json('data.tenants'));
        $this->assertSame('X', $r->json('data.tenants.0.name'));
    }

    public function test_update_user_name_email(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->patchJson("/api/v1/admin/users/{$u->id}", ['name' => 'New', 'email' => 'new@x.vn'])
            ->assertOk()->assertJsonPath('data.email', 'new@x.vn');
        $this->assertSame('New', $u->fresh()->name);
    }

    public function test_update_rejects_duplicate_email(): void
    {
        $this->bootstrap();
        User::factory()->create(['email' => 'taken@x.vn']);
        $u = User::factory()->create();
        $this->patchJson("/api/v1/admin/users/{$u->id}", ['email' => 'taken@x.vn'])
            ->assertStatus(422);
    }

    public function test_suspend_user_sets_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend", ['reason' => 'Nghi ngờ gian lận đơn hàng.'])
            ->assertOk();
        $this->assertNotNull($u->fresh()->suspended_at);
    }

    public function test_reactivate_clears_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create(['suspended_at' => now()]);
        $this->postJson("/api/v1/admin/users/{$u->id}/reactivate", ['reason' => 'Đã xác minh lại danh tính khách hàng.'])
            ->assertOk();
        $this->assertNull($u->fresh()->suspended_at);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend", ['reason' => 'ngắn'])
            ->assertStatus(422);
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend")
            ->assertStatus(422);
    }

    public function test_reactivate_requires_reason(): void
    {
        $this->bootstrap();
        $u = User::factory()->create(['suspended_at' => now()]);
        $this->postJson("/api/v1/admin/users/{$u->id}/reactivate")
            ->assertStatus(422);
    }

    public function test_reset_password_sets_new_hash(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/reset-password", ['password' => 'newpwd99'])->assertOk();
        $this->assertTrue(Hash::check('newpwd99', $u->fresh()->password));
    }

    public function test_suspend_writes_audit_log(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend", ['reason' => 'Nghi ngờ gian lận đơn hàng.'])
            ->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.user.suspend',
            'auditable_id' => $u->id,
        ]);

        $log = AuditLog::query()
            ->where('action', 'admin.user.suspend')
            ->where('auditable_id', $u->id)
            ->latest('id')
            ->first();
        $this->assertSame('Nghi ngờ gian lận đơn hàng.', $log->changes['reason'] ?? null);
    }
}
