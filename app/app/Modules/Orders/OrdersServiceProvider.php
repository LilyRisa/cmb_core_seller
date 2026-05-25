<?php

namespace CMBcoreSeller\Modules\Orders;

use CMBcoreSeller\Modules\Orders\Contracts\OrderUpsertContract;
use CMBcoreSeller\Modules\Orders\Contracts\ReturnUpsertContract;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Orders\Services\ReturnUpsertService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Orders domain module.
 *
 * See docs/01-architecture/modules.md — this module talks to other modules
 * only through Contracts/ interfaces and domain events, never internals.
 */
class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The Channels sync jobs push orders in via this contract (never via the Service directly).
        $this->app->bind(OrderUpsertContract::class, OrderUpsertService::class);
        // After-sales (cancel/return/refund) upsert — SPEC 0025.
        $this->app->bind(ReturnUpsertContract::class, ReturnUpsertService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
