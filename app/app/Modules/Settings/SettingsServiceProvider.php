<?php

namespace CMBcoreSeller\Modules\Settings;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Support\Facades\Event;
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

        Event::listen(
            Events\SystemSettingChanged::class,
            Listeners\LogSystemSettingChanged::class,
        );

        $this->applyMailConfigFromDb();
    }

    /**
     * Đẩy cấu hình email từ DB (catalog nhóm `mail`, có cache) đè lên `config('mail.*')` — fallback config/env.
     * Cho phép super-admin đổi SMTP/from ở /admin/system-settings (tab "Email") không cần re-deploy.
     * DB lỗi/chưa set ⇒ `system_setting()` trả default = giá trị env hiện tại (giữ nguyên hành vi).
     */
    private function applyMailConfigFromDb(): void
    {
        config([
            'mail.default' => system_setting('mail.mailer', config('mail.default')),
            'mail.mailers.smtp.host' => system_setting('mail.host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => (int) system_setting('mail.port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => system_setting('mail.username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => system_setting('mail.password', config('mail.mailers.smtp.password')),
            'mail.mailers.smtp.scheme' => system_setting('mail.scheme', config('mail.mailers.smtp.scheme')),
            'mail.from.address' => system_setting('mail.from_address', config('mail.from.address')),
            'mail.from.name' => system_setting('mail.from_name', config('mail.from.name')),
        ]);
    }
}
