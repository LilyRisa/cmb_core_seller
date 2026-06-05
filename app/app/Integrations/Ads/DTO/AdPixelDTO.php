<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdPixelDTO
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
