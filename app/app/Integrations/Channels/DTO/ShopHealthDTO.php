<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Báo cáo sức khỏe/hiệu suất một gian hàng (scorecard) — chuẩn hoá đa sàn.
 *
 * `kind`:
 *  - 'health'      : Lazada/Shopee — chỉ số sức khỏe + mục tiêu (đạt/không).
 *  - 'performance' : TikTok — chỉ có hiệu suất doanh thu/CSKH (không có rating sức khỏe qua API).
 *
 * `overallRating` (nếu có): Shopee 1=Poor · 2=ImprovementNeeded · 3=Good · 4=Excellent.
 */
final readonly class ShopHealthDTO
{
    /**
     * @param  list<ShopMetricDTO>  $metrics
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $provider,
        public string $kind,            // health | performance
        public ?int $overallRating,     // 1..4 (Shopee) | null
        public ?string $overallLabel,   // "Tốt"/"Cần cải thiện"... | null
        public array $metrics,
        public array $raw = [],
    ) {}
}
