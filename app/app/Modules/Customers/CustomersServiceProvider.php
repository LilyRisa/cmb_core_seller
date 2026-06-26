<?php

namespace CMBcoreSeller\Modules\Customers;

use CMBcoreSeller\Modules\Channels\Events\ChannelAccountRevoked;
use CMBcoreSeller\Modules\Channels\Events\DataDeletionRequested;
use CMBcoreSeller\Modules\Customers\Console\BackfillCustomers;
use CMBcoreSeller\Modules\Customers\Console\RecomputeStaleCustomers;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerProfileContract;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerReportContract;
use CMBcoreSeller\Modules\Customers\Listeners\LinkOrderToCustomer;
use CMBcoreSeller\Modules\Customers\Listeners\OnChannelAccountRevoked;
use CMBcoreSeller\Modules\Customers\Listeners\OnDataDeletionRequested;
use CMBcoreSeller\Modules\Customers\Services\CustomerProfileResolver;
use CMBcoreSeller\Modules\Customers\Services\CustomerReportService;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Customers domain module — internal buyer registry &
 * reputation (SPEC 0002). Talks to Orders/Channels only via events + the
 * CustomerProfileContract; never the other way's Services.
 * See docs/01-architecture/modules.md.
 */
class CustomersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // config/customers.php is auto-loaded by the framework.
        // The only way other modules read the registry:
        $this->app->bind(CustomerProfileContract::class, CustomerProfileResolver::class);
        // Orders hỏi trạng thái report "bom hàng" của đơn qua contract này (SPEC 0038 v2).
        $this->app->bind(CustomerReportContract::class, CustomerReportService::class);
        // Ví trả trước: Orders trừ/hoàn ví qua contract này (SPEC 2026-06-26).
        $this->app->bind(
            \CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet::class,
            \CMBcoreSeller\Modules\Customers\Services\CustomerWalletService::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Event::listen(OrderUpserted::class, LinkOrderToCustomer::class);
        Event::listen(DataDeletionRequested::class, OnDataDeletionRequested::class);
        Event::listen(ChannelAccountRevoked::class, OnChannelAccountRevoked::class);
        // Hoàn ví trả trước khi đơn bị huỷ/hoàn (SPEC 2026-06-26).
        Event::listen(\CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged::class, \CMBcoreSeller\Modules\Customers\Listeners\RefundWalletOnOrderCancelled::class);

        if ($this->app->runningInConsole()) {
            $this->commands([BackfillCustomers::class, RecomputeStaleCustomers::class]);
        }

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
