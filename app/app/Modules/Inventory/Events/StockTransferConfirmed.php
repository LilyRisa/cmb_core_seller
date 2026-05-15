<?php

namespace CMBcoreSeller\Modules\Inventory\Events;

use CMBcoreSeller\Modules\Inventory\Models\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired sau khi {@see \CMBcoreSeller\Modules\Inventory\Services\WarehouseDocumentService::confirmTransfer}
 * áp tồn vào sổ cái (transferOut from + transferIn to). Phase 7.1 — module Accounting
 * listen để hạch toán Dr 156 (kho đến) / Cr 156 (kho đi) theo giá vốn bình quân của SKU tại
 * kho nguồn lúc chuyển.
 */
class StockTransferConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly StockTransfer $transfer) {}
}
