<?php

namespace CMBcoreSeller\Modules\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Fulfillment domain module.
 *
 * See docs/01-architecture/modules.md — this module talks to other modules
 * only through Contracts/ interfaces and domain events, never internals.
 */
class FulfillmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FieldTypeRegistry::class, function ($app) {
            $r = new FieldTypeRegistry();
            foreach ([
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\QrField::class,
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\BarcodeField::class,
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\TextField::class,
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ImageField::class,
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DataField::class,
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ItemsListField::class,
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DividerField::class,
                \CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\RectangleField::class,
            ] as $cls) {
                $r->register($app->make($cls));
            }

            return $r;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
