<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogsAdminUserIdMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_has_admin_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('audit_logs', 'admin_user_id'));
    }

    public function test_users_has_suspended_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'suspended_at'));
    }

    public function test_audit_log_record_writes_admin_actor_when_logged_in_as_admin(): void
    {
        $admin = AdminUser::factory()->create();
        Auth::guard('admin_web')->login($admin);

        AuditLog::record('admin.test.action');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.test.action',
            'admin_user_id' => $admin->id,
            'user_id' => null,
            'tenant_id' => null,
        ]);
    }
}
