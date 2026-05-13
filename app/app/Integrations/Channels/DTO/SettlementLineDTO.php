<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * 1 dòng đối soát — phân loại phí/khoản theo `feeType` chuẩn của core. SPEC 0016.
 *
 * `feeType` chuẩn (`SettlementLineDTO::TYPES`):
 *   - `revenue`            : tiền hàng người mua trả (dương).
 *   - `commission`         : hoa hồng sàn thu (âm).
 *   - `payment_fee`        : phí thanh toán (âm).
 *   - `shipping_fee`       : phí ship sàn thu/trả lại (âm/dương).
 *   - `shipping_subsidy`   : trợ giá ship sàn cho seller (dương).
 *   - `voucher_seller`     : voucher do seller chi (âm).
 *   - `voucher_platform`   : voucher sàn chi (dương).
 *   - `adjustment`         : điều chỉnh khác (âm/dương).
 *   - `refund`             : hoàn tiền cho khách (âm).
 *   - `other`              : chưa map được (giữ raw để xem sau).
 */
final readonly class SettlementLineDTO
{
    public const TYPE_REVENUE = 'revenue';

    public const TYPE_COMMISSION = 'commission';

    public const TYPE_PAYMENT_FEE = 'payment_fee';

    public const TYPE_SHIPPING_FEE = 'shipping_fee';

    public const TYPE_SHIPPING_SUBSIDY = 'shipping_subsidy';

    public const TYPE_VOUCHER_SELLER = 'voucher_seller';

    public const TYPE_VOUCHER_PLATFORM = 'voucher_platform';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_REVENUE, self::TYPE_COMMISSION, self::TYPE_PAYMENT_FEE, self::TYPE_SHIPPING_FEE,
        self::TYPE_SHIPPING_SUBSIDY, self::TYPE_VOUCHER_SELLER, self::TYPE_VOUCHER_PLATFORM,
        self::TYPE_ADJUSTMENT, self::TYPE_REFUND, self::TYPE_OTHER,
    ];

    /** @param  array<string,mixed>  $raw */
    public function __construct(
        public string $feeType,
        public int $amount,                  // VND đồng — dương: thu vào, âm: chi ra (theo góc nhìn seller).
        public ?string $externalOrderId = null,
        public ?string $externalLineId = null,
        public ?CarbonImmutable $occurredAt = null,
        public ?string $description = null,
        public array $raw = [],
    ) {}
}
