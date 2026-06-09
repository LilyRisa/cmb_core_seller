<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class PageRefDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $id,
        public string $name,
        public string $accessToken,
        public ?string $pictureUrl = null,
        public array $raw = [],
    ) {}
}
