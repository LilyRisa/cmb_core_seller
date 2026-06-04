<?php

namespace CMBcoreSeller\Integrations\Ads\Contracts;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;

/**
 * Segregated WRITE axis for ads providers — implemented only by providers that
 * support ad creation. Read-only providers implement {@see AdsConnector} alone.
 * Callers MUST check `instanceof AdsWriteConnector` + `supports('ads.create')`.
 */
interface AdsWriteConnector
{
    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string;

    public function createAdSet(string $accessToken, string $externalAccountId, AdSetSpecDTO $spec): string;

    public function createAd(string $accessToken, string $externalAccountId, AdSpecDTO $spec): string;
}
