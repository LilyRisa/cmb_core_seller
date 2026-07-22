<?php

namespace CMBcoreSeller\Modules\Notifications\Console\Commands;

use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Plan A (2026-07-23) — chạy 1 LẦN sau migration thêm cột `category` (Task 1), để gán
 * category='order' cho row cũ có type thuộc nhóm đơn hàng (cột mới mặc định 'system').
 * Idempotent — chạy lại không đổi gì. Quét toàn bộ tenant (withoutGlobalScope vì chạy
 * ngoài request context, không có tenant hiện tại).
 */
class BackfillNotificationCategory extends Command
{
    protected $signature = 'notifications:backfill-category';

    protected $description = 'Backfill category=order cho app_notifications cũ có type thuộc nhóm đơn hàng (Plan A, 2026-07-23)';

    private const ORDER_TYPES = ['order.negative_total', 'order.cancelled', 'order.return_new'];

    public function handle(): int
    {
        $updated = 0;
        Notification::withoutGlobalScope(TenantScope::class)
            ->whereIn('type', self::ORDER_TYPES)
            ->where('category', '!=', 'order')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$updated) {
                foreach ($rows as $row) {
                    $row->forceFill(['category' => 'order'])->save();
                    $updated++;
                }
            });

        $this->info("Đã backfill category='order' cho {$updated} thông báo.");

        return self::SUCCESS;
    }
}
