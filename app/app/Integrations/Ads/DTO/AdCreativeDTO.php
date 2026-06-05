<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdCreativeDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $adId,
        public ?string $adName,
        public ?string $effectiveStatus,
        public ?string $primaryText,
        public ?string $headline,
        public ?string $cta,
        public ?string $pagePostId,
        public ?string $linkUrl = null,   // landing page / destination URL (website ads)
        public array $raw = [],
    ) {}
}
