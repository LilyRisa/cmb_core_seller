<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class TargetingOptionDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public ?int $audienceSize = null,
        public array $raw = [],
    ) {}
}
