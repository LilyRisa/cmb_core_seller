<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdEntityDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $level,            // campaign | adset | ad
        public string $externalId,
        public ?string $parentExternalId,
        public ?string $name,
        public ?string $status,
        public ?string $effectiveStatus,
        public ?int $dailyBudget,        // minor units
        public ?int $lifetimeBudget,
        public ?string $objective = null,   // campaign objective (loại chiến dịch)
        public array $raw = [],
    ) {}
}
