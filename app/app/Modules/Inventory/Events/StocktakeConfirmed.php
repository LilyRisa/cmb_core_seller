<?php

namespace CMBcoreSeller\Modules\Inventory\Events;

use CMBcoreSeller\Modules\Inventory\Models\Stocktake;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired sau khi {@see \CMBcoreSeller\Modules\Inventory\Services\WarehouseDocumentService::confirmStocktake}
 * áp diff vào sổ cái. Phase 7.1 — module Accounting listen để hạch toán:
 *   - diff > 0 (thừa) ⇒ Dr 156 / Cr 711 (thu nhập khác)
 *   - diff < 0 (thiếu) ⇒ Dr 811 (chi phí khác) / Cr 156
 */
class StocktakeConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Stocktake $stocktake) {}
}
