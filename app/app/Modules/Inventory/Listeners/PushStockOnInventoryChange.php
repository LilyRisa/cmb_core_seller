<?php

namespace CMBcoreSeller\Modules\Inventory\Listeners;

use CMBcoreSeller\Modules\Inventory\Events\InventoryChanged;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;

/**
 * Stock changed → schedule a (debounced) per-SKU recompute-and-push. The ~10s
 * delay + PushStockForSku's ShouldBeUnique coalesce a burst of changes into one
 * push per SKU. See SPEC 0003 §3-4.
 */
class PushStockOnInventoryChange
{
    public function handle(InventoryChanged $event): void
    {
        foreach (array_unique($event->skuIds) as $skuId) {
            PushStockForSku::dispatch($event->tenantId, (int) $skuId)->delay(now()->addSeconds(10));
        }
    }
}
