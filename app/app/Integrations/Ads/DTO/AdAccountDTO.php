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
        public ?int $accountStatus = null,     // FB account_status code (health)
        public ?int $disableReason = null,     // FB disable_reason code
        public array $raw = [],
    ) {}
}
