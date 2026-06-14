<?php

namespace CMBcoreSeller\Modules\Inventory\Listeners;

use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Inventory\Models\StockPushLog;

/**
 * Ghi 1 dòng lịch sử mỗi lần đẩy tồn lên sàn (thành công hoặc thất bại cuối cùng).
 * Chạy trong queue worker (job PushStockToListing) — set tenant_id tường minh từ
 * listing vì không có CurrentTenant trong ngữ cảnh queue.
 */
class RecordStockPushLog
{
    public function handle(StockPushed $event): void
    {
        $l = $event->listing;

        StockPushLog::create([
            'tenant_id' => $l->tenant_id,
            'channel_listing_id' => $l->getKey(),
            'channel_account_id' => $l->channel_account_id,
            'seller_sku' => $l->seller_sku,
            'external_sku_id' => $l->external_sku_id,
            'desired_qty' => $event->desired,
            'status' => $event->ok ? StockPushLog::STATUS_OK : StockPushLog::STATUS_FAILED,
            'error' => $event->ok ? null : $l->sync_error,
        ]);
    }
}
