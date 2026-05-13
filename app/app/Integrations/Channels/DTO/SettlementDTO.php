<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Normalized **đối soát/settlement** từ sàn — đại diện 1 kỳ thanh toán (statement). Core không biết shape
 * thô của TikTok/Lazada/Shopee — chỉ thấy DTO này. Tiền là số nguyên VND đồng. SPEC 0016.
 */
final readonly class SettlementDTO
{
    /**
     * @param  ?string  $externalId  mã statement do sàn cấp (nếu có); ngược lại dedupe theo (channel_account, period_start, period_end).
     * @param  list<SettlementLineDTO>  $lines  từng dòng phí/doanh thu/điều chỉnh (có thể bao đơn cụ thể).
     * @param  array<string,mixed>  $raw  payload thô để re-process.
     */
    public function __construct(
        public ?string $externalId,
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public int $totalPayout,             // = totalRevenue + totalFee + totalShippingFee + adjustments (fee âm)
        public int $totalRevenue = 0,
        public int $totalFee = 0,            // phí sàn (commission + payment + ads), số ÂM (chi)
        public int $totalShippingFee = 0,    // phí ship sàn thu / trả lại, có thể âm/dương
        public string $currency = 'VND',
        public array $lines = [],
        public ?CarbonImmutable $paidAt = null,
        public array $raw = [],
    ) {}
}
