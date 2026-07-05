<?php

namespace CMBcoreSeller\Integrations\Ads\Contracts;

use CMBcoreSeller\Integrations\Ads\DTO\AdPixelDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AudienceSizeDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO;
use CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO;

/**
 * Segregated ad authoring axis (create + creative/targeting/preview queries) —
 * implemented only by providers that support ad creation. Read-only providers
 * implement {@see AdsConnector} alone.
 * Callers MUST check `instanceof AdsWriteConnector` + `supports('ads.create')`.
 */
interface AdsWriteConnector
{
    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string;

    public function createAdSet(string $accessToken, string $externalAccountId, AdSetSpecDTO $spec): string;

    public function createAd(string $accessToken, string $externalAccountId, AdSpecDTO $spec): string;

    /**
     * Update mutable fields of one entity (campaign/adset/ad) in place.
     * Supported keys: name (string), daily_budget_major (int VND), status (ACTIVE|PAUSED).
     *
     * @param  array<string,mixed>  $fields
     */
    public function updateEntity(string $accessToken, string $level, string $externalId, array $fields, string $currency = 'VND'): void;

    /** @return list<PageRefDTO> */
    public function listPages(string $accessToken): array;

    /** @return list<AdPixelDTO> */
    public function listPixels(string $accessToken, string $externalAccountId): array;

    /** Share a pixel with another ad account (by the pixel's owning business + target account). */
    public function sharePixel(string $accessToken, string $pixelId, string $businessId, string $targetAccountId): void;

    /** @return list<PagePostDTO> */
    public function listPagePosts(string $pageAccessToken, string $pageId, int $limit = 25): array;

    /** Fetch ONE page post by its full id ("<page_id>_<post_id>"); null if not found. */
    public function getPagePost(string $pageAccessToken, string $postId): ?PagePostDTO;

    /**
     * Batch-read engagement (likes/comments/shares/message) for the given post ids.
     *
     * @param  list<string>  $postIds
     * @return array<string, array{likes:int, comments:int, shares:int, message:?string}>
     */
    public function fetchPostEngagement(string $accessToken, array $postIds): array;

    /**
     * Resolve the destination URL of each post (effective_object_story_id) from its
     * page-post call-to-action — for ads built from an existing post, whose creative
     * carries no link of its own. Best-effort: unknown pages / errors are skipped.
     *
     * @param  list<string>  $postIds  "<page_id>_<post_id>" values
     * @return array<string,string> postId => destination URL
     */
    public function fetchPostLinks(string $accessToken, array $postIds): array;

    /** @return list<TargetingOptionDTO> */
    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array;

    /** @param array<string,mixed> $targetingSpec */
    public function estimateAudience(string $accessToken, string $externalAccountId, array $targetingSpec, string $optimizationGoal): AudienceSizeDTO;

    /**
     * @param  array<string,mixed>  $creativeSpec
     * @param  list<string>  $formats
     * @return list<AdPreviewDTO>
     */
    public function generatePreviews(string $accessToken, string $externalAccountId, array $creativeSpec, array $formats): array;
}
