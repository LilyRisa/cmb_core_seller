<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use PHPUnit\Framework\TestCase;

class AdsWriteConnectorContractTest extends TestCase
{
    public function test_facebook_connector_implements_write_contract(): void
    {
        $c = new FacebookAdsConnector(['graph_version' => 'v19.0']);
        $this->assertInstanceOf(AdsWriteConnector::class, $c);
        $this->assertTrue($c->supports('ads.create'));
    }
}
