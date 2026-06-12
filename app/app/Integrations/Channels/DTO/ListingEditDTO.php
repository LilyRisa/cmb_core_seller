<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Edit payload for an existing marketplace product. A null field means "leave
 * unchanged"; a non-null field is pushed to the marketplace.
 *
 * Stock is intentionally NOT editable here — stock is pushed from the linked master
 * SKU(s) (sku_mappings). Prices are integer VND đồng, one entry per SKU/variant.
 * Images are SOURCE URLs; the connector uploads them to the marketplace itself.
 */
final readonly class ListingEditDTO
{
    /**
     * @param  string[]|null  $images  source image URLs to set as the main images
     * @param  array<int,array{external_sku_id:string,price:int}>|null  $prices
     * @param  array<string,mixed>  $rawDetail  the raw getListingDetail payload (context a connector may need, e.g. variant ids)
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?array $images = null,
        public ?array $prices = null,
        public array $rawDetail = [],
    ) {}

    public function hasInfo(): bool
    {
        return $this->title !== null || $this->description !== null || $this->images !== null;
    }

    public function hasPrices(): bool
    {
        return $this->prices !== null && $this->prices !== [];
    }
}
