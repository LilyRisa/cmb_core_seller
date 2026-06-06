<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Một bản ghi điểm phạt ("sao quả tạ") — hiện chỉ Shopee
 * (`account_health.get_penalty_point_history`). Điểm phạt tính theo quý hiện tại.
 */
final readonly class PenaltyPointDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public int $points,                 // latest_point_num
        public ?int $violationType = null,
        public ?string $violationLabel = null,
        public ?CarbonImmutable $issuedAt = null,
        public ?string $referenceId = null,
        public array $raw = [],
    ) {}
}
