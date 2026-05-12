<?php

namespace CMBcoreSeller\Modules\Inventory\Listeners;

use CMBcoreSeller\Modules\Inventory\Services\OrderInventoryService;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * On every OrderUpserted (any source), resolve order lines to master SKUs and
 * apply the stock effect for the current order status (reserve / ship / release /
 * return). Idempotent — the ledger dedupes per (order_item, sku, type). Replaces
 * the Phase-1 no-op stub. See SPEC 0003 §3-4.
 */
class ApplyOrderInventoryEffects implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(OrderUpserted $event): void
    {
        app(OrderInventoryService::class)->apply($event->order);
    }
}
