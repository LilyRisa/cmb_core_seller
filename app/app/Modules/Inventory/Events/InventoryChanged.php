<?php

namespace CMBcoreSeller\Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after any stock change. Carries the affected SKU ids so listeners (push
 * stock to channels, alerts) can react. See SPEC 0003 §5.
 */
class InventoryChanged
{
    use Dispatchable;

    /** @param list<int> $skuIds */
    public function __construct(public readonly int $tenantId, public readonly array $skuIds, public readonly string $reason) {}
}
