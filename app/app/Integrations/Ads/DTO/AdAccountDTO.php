<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdAccountDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $externalAccountId,   // act_<id>
        public ?string $name = null,
        public ?string $currency = null,
        public ?string $status = null,
        public ?string $businessId = null,     // Business Manager id
        public ?string $businessName = null,
        public ?string $businessPictureUrl = null, // BM logo
        public array $raw = [],
    ) {}
}
