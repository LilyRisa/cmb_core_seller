<?php

namespace CMBcoreSeller\Modules\Inventory\Events;

use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired sau khi WarehouseDocumentService::confirmGoodsIssue đã áp tồn (on_hand -= qty).
 * Các module khác (vd Accounting) có thể listen để hạch toán xuất kho.
 */
class GoodsIssueConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly GoodsIssue $issue) {}
}
