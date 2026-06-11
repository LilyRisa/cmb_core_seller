<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\BrandDTO;
use CMBcoreSeller\Integrations\Channels\DTO\CategoryNodeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingAttributeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingStatusDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;

/**
 * Contract for marketplace connectors that support product publishing.
 *
 * Implement this interface alongside {@see ChannelConnector} when a provider
 * supports listing creation/management. Core never calls this directly by
 * provider name — it goes through the capability flag `listings.publish`.
 */
interface ProductPublishingConnector
{
    /**
     * Return the category tree rooted at $parentId (null = root).
     *
     * @return CategoryNodeDTO[]
     */
    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array;

    /**
     * Return the attributes required/optional for a given leaf category.
     *
     * @return ListingAttributeDTO[]
     */
    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array;

    /**
     * Return brands available for a given category.
     *
     * @return BrandDTO[]
     */
    public function getBrands(AuthContext $auth, string $categoryId): array;

    /**
     * Upload an image (URL or local path) and return a marketplace media ref.
     * $useCase hints which slot the image is for ('main', 'variant', 'sku', …).
     */
    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO;

    /**
     * Create a new product listing from a draft; returns the marketplace result.
     */
    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO;

    /**
     * Fetch the current publish status of an existing listing by its external item id.
     */
    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO;
}
