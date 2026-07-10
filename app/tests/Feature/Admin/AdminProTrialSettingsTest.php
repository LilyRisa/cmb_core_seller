<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProTrialSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_and_read_settings(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin_web')
            ->putJson('/api/v1/admin/pro-trial-settings', [
                'enabled' => true, 'duration_days' => 30,
                'window_start' => '2026-07-10', 'window_end' => '2026-08-10',
            ])->assertOk();

        $this->assertTrue(ProTrialSettings::enabled());
        $this->assertSame(30, ProTrialSettings::durationDays());

        $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/pro-trial-settings')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.duration_days', 30)
            ->assertJsonPath('data.window_start', '2026-07-10')
            ->assertJsonPath('data.window_end', '2026-08-10');
    }

    public function test_requires_admin_session(): void
    {
        $this->getJson('/api/v1/admin/pro-trial-settings')->assertStatus(401);
    }

    public function test_clearing_window_writes_null(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin_web')
            ->putJson('/api/v1/admin/pro-trial-settings', [
                'enabled' => false, 'duration_days' => 14,
                'window_start' => null, 'window_end' => null,
            ])->assertOk()
            ->assertJsonPath('data.window_start', null)
            ->assertJsonPath('data.window_end', null);

        $this->assertNull(ProTrialSettings::windowStart());
        $this->assertNull(ProTrialSettings::windowEnd());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.pro_trial.settings',
            'tenant_id' => null,
        ]);
    }
}
