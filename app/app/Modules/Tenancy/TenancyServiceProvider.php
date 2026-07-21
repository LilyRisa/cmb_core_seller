<?php

namespace CMBcoreSeller\Modules\Tenancy;

use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use CMBcoreSeller\Modules\Tenancy\Listeners\LogUserLogin;
use CMBcoreSeller\Modules\Tenancy\Listeners\ReportSignupToMetaCapi;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Tenancy domain module.
 *
 * See docs/01-architecture/modules.md — this module talks to other modules
 * only through Contracts/ interfaces and domain events, never internals.
 */
class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One CurrentTenant per request/job (reset between requests).
        $this->app->scoped(CurrentTenant::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        // Ghi lịch sử đăng nhập nhân viên tenant — chỉ guard `web` (lọc trong listener).
        Event::listen(Login::class, LogUserLogin::class);

        // SPEC 2026-07-22 — báo CompleteRegistration về Meta Conversions API (best-effort, no-op
        // nếu chưa cấu hình Pixel ở /admin/settings).
        Event::listen(TenantCreated::class, ReportSignupToMetaCapi::class);

        // Permission gate: $user->can('orders.update') resolves to the current
        // tenant role's permission set. Returns null when not applicable so
        // other Gate definitions still run.
        Gate::before(function ($user, string $ability) {
            $current = app(CurrentTenant::class);

            if (! $current->check()) {
                return null;
            }

            return $current->can($ability) ?: null;
        });
    }
}
