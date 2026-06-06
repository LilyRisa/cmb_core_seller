<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Một sự kiện điểm phạt/vi phạm real-time bóc từ webhook sàn (vd Shopee
 * `shop_penalty_update_push` code 28, `violation_item_push` code 16). Chuẩn hoá
 * để core lưu/cảnh báo mà không cần biết shape của từng sàn.
 */
final readonly class PenaltyEventDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $kind,            // penalty_issued | penalty_removed | tier_update | listing_violation
        public int $points = 0,
        public ?int $violationType = null,
        public ?string $violationLabel = null,
        public ?int $tier = null,
        public ?string $itemId = null,
        public ?string $itemName = null,
        public ?CarbonImmutable $occurredAt = null,
        public array $raw = [],
    ) {}
}
