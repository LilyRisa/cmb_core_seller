<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;

/**
 * Full content of an existing marketplace product, fetched for editing.
 *
 * Prices are integer VND đồng (one entry per SKU/variant). Images are CDN URLs.
 * Returned by {@see ProductPublishingConnector::getListingDetail()}.
 */
final readonly class ListingDetailDTO
{
    /**
     * @param  string[]  $images  main image URLs in display order
     * @param  array<int,array{external_sku_id:string,seller_sku:string,price:int}>  $skus
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $externalProductId,
        public string $title,
        public string $description,
        public array $images,
        public array $skus,
        public array $raw = [],
    ) {}
}
