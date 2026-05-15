<?php

namespace CMBcoreSeller\Modules\Accounting;

use CMBcoreSeller\Modules\Accounting\Listeners\PostOnGoodsReceiptConfirmed;
use CMBcoreSeller\Modules\Accounting\Listeners\PostOnOrderShipped;
use CMBcoreSeller\Modules\Accounting\Listeners\PostOnStocktakeConfirmed;
use CMBcoreSeller\Modules\Accounting\Listeners\PostOnStockTransferConfirmed;
use CMBcoreSeller\Modules\Accounting\Services\PostRuleResolver;
use CMBcoreSeller\Modules\Inventory\Events\GoodsReceiptConfirmed;
use CMBcoreSeller\Modules\Inventory\Events\StocktakeConfirmed;
use CMBcoreSeller\Modules\Inventory\Events\StockTransferConfirmed;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider cho module Accounting (Phase 7+ — SPEC 0019).
 *
 * Tuân `01-architecture/modules.md` §3: module Accounting nói chuyện với module khác CHỈ qua
 * domain event + Contracts. Không import internals.
 */
class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // PostRuleResolver giữ cache per-tenant — singleton trong scope app instance.
        $this->app->singleton(PostRuleResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        // Listen Inventory events — Accounting ghi sổ kép. Idempotent (replay an toàn).
        // AccountingException render envelope chuẩn ở bootstrap/app.php.
        Event::listen(GoodsReceiptConfirmed::class, [PostOnGoodsReceiptConfirmed::class, 'handle']);
        Event::listen(StockTransferConfirmed::class, [PostOnStockTransferConfirmed::class, 'handle']);
        Event::listen(StocktakeConfirmed::class, [PostOnStocktakeConfirmed::class, 'handle']);
        Event::listen(OrderStatusChanged::class, [PostOnOrderShipped::class, 'handle']);
    }
}
