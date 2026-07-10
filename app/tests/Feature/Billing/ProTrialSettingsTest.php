<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_when_unset(): void
    {
        $this->assertFalse(ProTrialSettings::enabled());
        $this->assertSame(30, ProTrialSettings::durationDays());
        $this->assertNull(ProTrialSettings::windowStart());
    }

    public function test_reads_from_system_setting(): void
    {
        $svc = app(SystemSettingService::class);
        $svc->set('billing.pro_trial.enabled', true);
        $svc->set('billing.pro_trial.duration_days', 45);

        $this->assertTrue(ProTrialSettings::enabled());
        $this->assertSame(45, ProTrialSettings::durationDays());
    }

    public function test_window_open_respects_bounds(): void
    {
        $svc = app(SystemSettingService::class);
        $svc->set('billing.pro_trial.window_start', now()->subDay()->toDateString());
        $svc->set('billing.pro_trial.window_end', now()->addDay()->toDateString());
        $this->assertTrue(ProTrialSettings::windowOpen());

        $svc->set('billing.pro_trial.window_end', now()->subDay()->toDateString());
        $this->assertFalse(ProTrialSettings::windowOpen());
    }
}
