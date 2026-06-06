<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Một hình phạt đang/đã áp dụng lên gian hàng — hiện chỉ Shopee
 * (`account_health.get_punishment_history`): ẩn listing, cấm tạo/sửa, treo tài khoản,
 * giới hạn listing/đơn... `tier` = bậc (Tier 1..5).
 */
final readonly class PunishmentDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public ?int $type,
        public ?string $typeLabel,
        public ?int $tier = null,
        public ?CarbonImmutable $startAt = null,
        public ?CarbonImmutable $endAt = null,
        public bool $ongoing = true,
        public array $raw = [],
    ) {}
}
