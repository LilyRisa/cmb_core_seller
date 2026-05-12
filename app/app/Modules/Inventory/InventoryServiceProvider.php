<?php

namespace CMBcoreSeller\Modules\Inventory;

use CMBcoreSeller\Modules\Inventory\Events\InventoryChanged;
use CMBcoreSeller\Modules\Inventory\Listeners\ApplyOrderInventoryEffects;
use CMBcoreSeller\Modules\Inventory\Listeners\PushStockOnInventoryChange;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Inventory domain module.
 *
 * See docs/01-architecture/modules.md — this module talks to other modules
 * only through Contracts/ interfaces and domain events, never internals.
 */
class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Order lifecycle → stock effects (reserve/ship/release/return) + SKU resolution.
        Event::listen(OrderUpserted::class, ApplyOrderInventoryEffects::class);
        // Stock change → debounced push to linked channel listings.
        Event::listen(InventoryChanged::class, PushStockOnInventoryChange::class);

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
