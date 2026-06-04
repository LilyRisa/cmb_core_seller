<?php

namespace Tests\Feature\Settings;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_writes_audit_log_with_admin_actor(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web');

        app(SystemSettingService::class)->set('sync.backfill_days', 7);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.setting.update',
            'admin_user_id' => $admin->id,
        ]);
    }

    public function test_forget_also_writes_audit_log(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web');
        $svc = app(SystemSettingService::class);
        $svc->set('sync.backfill_days', 7);
        $svc->forget('sync.backfill_days');

        $this->assertSame(
            2,
            AuditLog::query()
                ->where('action', 'admin.setting.update')
                ->where('admin_user_id', $admin->id)
                ->count(),
        );
    }
}
