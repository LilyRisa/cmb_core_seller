<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Current status of a product listing on the marketplace.
 */
final readonly class ListingStatusDTO
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $externalItemId,
        public string $rawStatus,
        public string $normalized,
        public ?string $reason = null,
        public array $raw = [],
    ) {}
}
