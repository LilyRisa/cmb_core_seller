<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Normalized marketplace listing — one product variant (SKU) on a shop — as
 * returned by ChannelConnector::fetchListings(). Money is integer VND đồng.
 * See SPEC 0003 §5, docs/04-channels/README.md.
 */
final readonly class ChannelListingDTO
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $externalSkuId,
        public ?string $externalProductId = null,
        public ?string $sellerSku = null,
        public ?string $title = null,
        public ?string $variation = null,
        public ?int $price = null,
        public ?int $channelStock = null,
        public string $currency = 'VND',
        public ?string $image = null,
        public bool $isActive = true,
        public array $raw = [],
    ) {}
}
