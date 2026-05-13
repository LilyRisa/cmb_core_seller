<?php

namespace CMBcoreSeller\Modules\Inventory\Events;

use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired sau khi {@see \CMBcoreSeller\Modules\Inventory\Services\WarehouseDocumentService::confirmGoodsReceipt}
 * đã áp tồn vào sổ cái + cập nhật giá vốn. Các module khác (Procurement, FIFO cost layers) listen để
 * cộng dồn `qty_received` vào PO + tạo cost layer mới. SPEC 0014.
 */
class GoodsReceiptConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly GoodsReceipt $receipt) {}
}
