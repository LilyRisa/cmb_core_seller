<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Result returned by ChannelConnector::publishListing() on success.
 */
final readonly class ListingResultDTO
{
    /**
     * @param  array<string,string>  $skuMap
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $externalItemId,
        public array $skuMap = [],
        public string $rawStatus = '',
        public array $raw = [],
    ) {}
}
