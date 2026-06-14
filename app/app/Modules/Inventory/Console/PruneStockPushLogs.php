<?php

namespace CMBcoreSeller\Modules\Inventory\Console;

use CMBcoreSeller\Modules\Inventory\Models\StockPushLog;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Dọn lịch sử đẩy tồn (stock_push_logs) cũ hơn N ngày (mặc định 7) để bảng không
 * phình theo thời gian. Chạy theo lịch (routes/console.php) — không có tenant hiện
 * tại nên bỏ TenantScope để dọn toàn hệ thống.
 */
class PruneStockPushLogs extends Command
{
    protected $signature = 'inventory:prune-stock-push-logs {--days=7 : Giữ log trong N ngày gần nhất}';

    protected $description = 'Xóa lịch sử đẩy tồn cũ hơn N ngày (mặc định 7).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = StockPushLog::withoutGlobalScope(TenantScope::class)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Đã xóa {$deleted} dòng lịch sử đẩy tồn cũ hơn {$days} ngày.");

        return self::SUCCESS;
    }
}
