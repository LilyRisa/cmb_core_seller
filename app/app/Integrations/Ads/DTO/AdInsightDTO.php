<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdInsightDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $level,
        public string $externalId,
        public string $dateStart,
        public string $dateStop,
        public int $spend,           // minor units
        public int $impressions,
        public int $clicks,
        public int $reach,
        public ?float $ctr,
        public ?int $cpc,
        public ?int $cpm,
        public ?float $frequency,
        public ?float $purchaseRoas,
        public array $raw = [],
    ) {}
}
