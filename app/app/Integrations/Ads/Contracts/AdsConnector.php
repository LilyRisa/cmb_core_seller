<?php

namespace CMBcoreSeller\Integrations\Ads\Contracts;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\DTO\AdAccountDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdCreativeDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;

/**
 * Contract every ads provider implements (ADR-0017 — core/module never knows the
 * provider name; resolve via {@see AdsRegistry}).
 * Methods a provider lacks throw {@see UnsupportedOperation}.
 */
interface AdsConnector
{
    public function code(): string;

    public function displayName(): string;

    /** @return array<string,bool> */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    // --- OAuth ---
    public function buildAuthorizationUrl(string $state, array $opts = []): string;

    /** @return array{access_token:string, expires_at:?CarbonImmutable, raw:array<string,mixed>} */
    public function exchangeCodeForToken(string $code): array;

    // --- Read ---
    /** @return list<AdAccountDTO> */
    public function listAdAccounts(string $accessToken): array;

    /**
     * List entities of one level (campaign|adset|ad) for an account.
     *
     * @return list<AdEntityDTO>
     */
    public function listEntities(string $accessToken, string $externalAccountId, string $level): array;

    /**
     * Fetch insights for one object (account/campaign/adset/ad). The implementation
     * writes the throttle header snapshot into `$throttleOut` (by ref) for pacing.
     *
     * @param  array<string,mixed>  $query
     * @return list<AdInsightDTO>
     */
    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?AdInsightThrottleDTO &$throttleOut = null): array;

    /**
     * Read each ad's creative text (for content/quality analysis).
     *
     * @return list<AdCreativeDTO>
     */
    public function fetchAdCreatives(string $accessToken, string $externalAccountId): array;
}
