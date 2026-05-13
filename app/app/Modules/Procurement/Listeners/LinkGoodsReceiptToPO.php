<?php

namespace CMBcoreSeller\Modules\Procurement\Listeners;

use CMBcoreSeller\Modules\Inventory\Events\GoodsReceiptConfirmed;
use CMBcoreSeller\Modules\Procurement\Services\PurchaseOrderService;

/**
 * Khi một {@see \CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt} được confirm, cộng dồn `qty_received`
 * vào dòng PO tương ứng (nếu phiếu nhập link về PO qua `purchase_order_id`) và chuyển trạng thái PO. SPEC 0014.
 */
class LinkGoodsReceiptToPO
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    public function handle(GoodsReceiptConfirmed $event): void
    {
        $this->service->applyReceiptConfirmed($event->receipt);
    }
}
