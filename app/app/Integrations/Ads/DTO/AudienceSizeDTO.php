<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AudienceSizeDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public ?int $lowerBound,
        public ?int $upperBound,
        public array $raw = [],
    ) {}
}
