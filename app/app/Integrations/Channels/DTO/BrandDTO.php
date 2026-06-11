<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * A brand returned by the marketplace brand-lookup API.
 */
final readonly class BrandDTO
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $mandatory = false,
        public array $raw = [],
    ) {}
}
