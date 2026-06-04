<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdPreviewDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $format,
        public string $body,
        public array $raw = [],
    ) {}
}
