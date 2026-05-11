<?php

namespace CMBcoreSeller\Modules\Procurement;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Procurement domain module.
 *
 * See docs/01-architecture/modules.md — this module talks to other modules
 * only through Contracts/ interfaces and domain events, never internals.
 */
class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
