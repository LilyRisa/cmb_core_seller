<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use Carbon\CarbonImmutable;

/**
 * Tối ưu lịch chạy quảng cáo theo giờ hiện tại + timezone tài khoản QC.
 *
 * Ngân sách/ngày của Facebook reset lúc nửa đêm (theo tz tài khoản). Bắt đầu sát nửa đêm
 * ⇒ cả ngân sách ngày có thể bị tiêu trong ít giờ ("đốt tiền"). Service này CHỈ đề xuất giá
 * trị an toàn + cảnh báo mềm — KHÔNG ép; người dùng tự chọn ngày/giờ ở FE.
 */
final class ScheduleOptimizer
{
    public function __construct(private readonly int $minRunwayHours = 4) {}

    /** Giờ bắt đầu đề xuất: nếu còn < minRunwayHours tới nửa đêm ⇒ đầu ngày hôm sau; ngược lại = bây giờ. */
    public function recommendedStart(CarbonImmutable $now, string $timezone): CarbonImmutable
    {
        $local = $now->setTimezone($timezone);

        return $this->hoursToMidnight($local) < $this->minRunwayHours
            ? $local->addDay()->startOfDay()
            : $local;
    }

    /** Cảnh báo mềm nếu giờ bắt đầu quá sát nửa đêm (dễ tiêu hết ngân sách ngày). */
    public function riskWarning(CarbonImmutable $start, string $timezone): ?string
    {
        $local = $start->setTimezone($timezone);
        $hours = $this->hoursToMidnight($local);
        if ($hours >= $this->minRunwayHours) {
            return null;
        }
        $h = max(1, (int) round($hours));

        return "Bắt đầu lúc này chỉ còn ~{$h} giờ tới nửa đêm — ngân sách ngày dễ bị tiêu hết trong thời gian ngắn. "
            .'Cân nhắc đặt lịch bắt đầu sáng hôm sau.';
    }

    private function hoursToMidnight(CarbonImmutable $local): float
    {
        $nextMidnight = $local->addDay()->startOfDay();

        return $local->diffInMinutes($nextMidnight) / 60;
    }
}
