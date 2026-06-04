<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;

/**
 * Facebook Ads (Marketing API) connector — SPEC 2026-06-04. Reads ad accounts /
 * entities / insights via Graph; writes (budget/pause/bid) land in Phase 3.
 *
 * MONEY: Graph returns spend/cpc/cpm as major-unit decimal strings. Phase 1
 * targets VND (zero-decimal) ⇒ round() is exact. Multi-currency minor-unit
 * scaling (×100 for 2-decimal currencies, keyed on ad_accounts.currency) is a
 * later-phase concern.
 */
class FacebookAdsConnector implements AdsConnector
{
    /** @param array<string,mixed> $config config('integrations.ads_facebook') */
    public function __construct(private array $config) {}

    public function code(): string
    {
        return 'facebook';
    }

    public function displayName(): string
    {
        return 'Facebook Ads';
    }

    public function capabilities(): array
    {
        return [
            'insights.read' => true,
            'insights.async' => true,
            'entities.list' => true,
            'actions.budget' => false, // Phase 3
            'actions.status' => false, // Phase 3
            'actions.bid' => false,    // Phase 3
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl');
    }

    public function exchangeCodeForToken(string $code): array
    {
        throw UnsupportedOperation::for($this->code(), 'exchangeCodeForToken');
    }

    public function listAdAccounts(string $accessToken): array
    {
        throw UnsupportedOperation::for($this->code(), 'listAdAccounts');
    }

    public function listEntities(string $accessToken, string $externalAccountId, string $level): array
    {
        throw UnsupportedOperation::for($this->code(), 'listEntities');
    }

    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?AdInsightThrottleDTO &$throttleOut = null): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchInsights');
    }
}
