<?php

namespace CMBcoreSeller\Modules\Tenancy;

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
