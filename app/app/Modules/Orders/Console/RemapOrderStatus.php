<?php

namespace CMBcoreSeller\Modules\Orders\Console;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderStateMachine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Tính lại trạng thái chuẩn của đơn từ `raw_status` đã lưu, dùng bảng map HIỆN TẠI của connector — để áp
 * ngay các sửa đổi mapping (vd Lazada shipped_back_success→returned_refunded) cho ĐƠN CŨ mà KHÔNG cần sàn
 * đổi `update_time` (sync thường sẽ bỏ qua đơn không đổi do guard `source_updated_at`).
 *
 * AN TOÀN: chỉ đổi khi mapping mới TIẾN tới (rank cao hơn) hoặc sang nhánh huỷ/hoàn/giao-thất-bại — KHÔNG
 * kéo lùi đơn đã tiến nội bộ (vd đã markPacked lên ready_to_ship/shipped). Idempotent.
 *
 *   php artisan orders:remap-status                 # mọi sàn
 *   php artisan orders:remap-status --source=lazada # 1 sàn
 *   php artisan orders:remap-status --dry-run       # chỉ liệt kê, không ghi
 */
class RemapOrderStatus extends Command
{
    protected $signature = 'orders:remap-status {--source= : Lọc theo sàn (lazada|shopee|tiktok)} {--dry-run : Chỉ in, không ghi}';

    protected $description = 'Tính lại trạng thái đơn từ raw_status theo bảng map hiện tại (áp fix mapping cho đơn cũ).';

    public function handle(ChannelRegistry $registry, OrderStateMachine $sm): int
    {
        $dry = (bool) $this->option('dry-run');
        $changed = 0;
        $scanned = 0;

        Order::withoutGlobalScope(TenantScope::class)
            ->whereNotNull('raw_status')->where('raw_status', '!=', '')
            ->whereNotNull('channel_account_id')   // đơn sàn (đơn manual không có raw map)
            ->when($this->option('source'), fn ($q, $src) => $q->where('source', $src))
            ->orderBy('id')
            ->chunkById(500, function ($orders) use ($registry, $sm, $dry, &$changed, &$scanned) {
                foreach ($orders as $o) {
                    $scanned++;
                    if (! $registry->has((string) $o->source)) {
                        continue;
                    }
                    $mapped = $registry->for((string) $o->source)->mapStatus((string) $o->raw_status, (array) ($o->raw_payload ?? []));
                    // Chỉ áp khi KHÔNG phải kéo lùi (tiến tới, hoặc nhánh huỷ/hoàn/giao-thất-bại) — tránh
                    // ghi đè đơn đã tiến nội bộ (markPacked/handover) mà sàn chưa kịp phản ánh.
                    if ($mapped === $o->status || $sm->isBackwardJump($o->status, $mapped)) {
                        continue;
                    }
                    $this->line("#{$o->getKey()} {$o->source} [{$o->raw_status}] {$o->status->value} → {$mapped->value}");
                    if (! $dry) {
                        $o->forceFill(['status' => $mapped])->save();
                    }
                    $changed++;
                }
            });

        $this->info(($dry ? '[DRY-RUN] ' : '')."Quét {$scanned} đơn — remap {$changed} đơn.");

        return self::SUCCESS;
    }
}
