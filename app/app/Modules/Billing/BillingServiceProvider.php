<?php

namespace CMBcoreSeller\Modules\Billing;

use CMBcoreSeller\Modules\Billing\Console\CheckExpiringSubscriptionsCommand;
use CMBcoreSeller\Modules\Billing\Console\RecomputeUsageCommand;
use CMBcoreSeller\Modules\Billing\Events\InvoicePaid;
use CMBcoreSeller\Modules\Billing\Listeners\ActivateSubscription;
use CMBcoreSeller\Modules\Billing\Listeners\StartTrialSubscription;
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Billing domain module.
 *
 * See docs/01-architecture/modules.md — this module talks to other modules
 * only through Contracts/ interfaces and domain events, never internals.
 *
 * Phase 6.4 (SPEC 0018):
 * - Listen `TenantCreated` (Tenancy) ⇒ khởi động trial 14 ngày (PR1).
 * - Listen `InvoicePaid` ⇒ `ActivateSubscription` (PR2).
 * - Commands: `subscriptions:check-expiring` (hằng ngày — state machine), `billing:recompute-usage` (hằng giờ — lưới an toàn).
 */
class BillingServiceProvider extends ServiceProvider
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

        Event::listen(TenantCreated::class, StartTrialSubscription::class);
        Event::listen(InvoicePaid::class, ActivateSubscription::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckExpiringSubscriptionsCommand::class,
                RecomputeUsageCommand::class,
            ]);
        }
    }
}
