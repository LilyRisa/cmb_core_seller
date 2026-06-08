<?php

namespace CMBcoreSeller\Modules\Marketing\Events;

use CMBcoreSeller\Modules\Marketing\Services\AdMonitorEvaluator;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát bởi {@see AdMonitorEvaluator} khi một
 * chiến dịch/nhóm QC ĐANG TIẾN GẦN ngưỡng tắt (`pause_above`) nhưng CHƯA vượt — chi
 * phí/kết quả (hoặc spend khi 0 kết quả) ≥ APPROACHING_RATIO × pause_above (SPEC 0036).
 *
 * Mang scalar (không mang model) — tenant_id tường minh để Notifications fan-out dù
 * evaluator chạy ngoài tenant context.
 */
class AdMonitorThresholdApproaching
{
    use Dispatchable;

    public function __construct(
        public int $tenantId,
        public int $monitorId,
        public string $name,
        public string $level,   // campaign|adset
        public ?int $cpr,       // chi phí/kết quả hiện tại (null nếu 0 kết quả)
        public int $threshold,  // pause_above
    ) {}
}
