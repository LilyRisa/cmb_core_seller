<?php

namespace CMBcoreSeller\Modules\Settings;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Support\ServiceProvider;

/**
 * Spec 2026-05-17 — module Settings cung cấp lớp đọc/ghi cấu hình động
 * (`system_setting()` + `/admin/system-settings/*`).
 *
 * - Service `SystemSettingService` register as singleton — request-scope memo.
 * - Migrations loaded từ `Database/Migrations/`.
 * - Routes loaded từ `Http/routes.php` (admin-only).
 * - Listener `LogSystemSettingChanged` đăng ký trong `boot()` để ghi audit.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SystemSettingService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        \Illuminate\Support\Facades\Event::listen(
            Events\SystemSettingChanged::class,
            Listeners\LogSystemSettingChanged::class,
        );
    }
}
