<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

/**
 * Khung an toàn ngân sách/ngày (VND nguyên) cho 2 chế độ:
 *  - test: ngân sách nhỏ để học/đo (audience/sáng tạo) trước khi nhân.
 *  - scale: ngân sách lớn cho audience/sáng tạo đã chứng minh hiệu quả.
 *
 * Dùng để KẸP đề xuất của AI vào khoảng hợp lệ (≥ tối thiểu FB, ≤ trần theo chế độ),
 * tránh AI đề xuất quá nhỏ (FB từ chối) hoặc quá lớn (rủi ro đốt tiền).
 */
final class AdBudgetGuardrails
{
    public const MODE_TEST = 'test';

    public const MODE_SCALE = 'scale';

    /** Tối thiểu an toàn (cao hơn mức sàn FB cho objective rẻ nhất). */
    private const MIN_DAILY_VND = 50000;

    private const MAX = [
        self::MODE_TEST => 500000,
        self::MODE_SCALE => 100000000,
    ];

    private const RECOMMENDED = [
        self::MODE_TEST => 150000,
        self::MODE_SCALE => 700000,
    ];

    public function maxFor(string $mode): int
    {
        return self::MAX[$mode] ?? self::MAX[self::MODE_SCALE];
    }

    public function recommended(string $mode): int
    {
        return self::RECOMMENDED[$mode] ?? self::RECOMMENDED[self::MODE_TEST];
    }

    /** Kẹp ngân sách/ngày vào khoảng hợp lệ; ≤0 ⇒ dùng đề xuất mặc định theo chế độ. */
    public function clamp(int $dailyMajor, string $mode): int
    {
        if ($dailyMajor <= 0) {
            return $this->recommended($mode);
        }

        return max(self::MIN_DAILY_VND, min($dailyMajor, $this->maxFor($mode)));
    }
}
