<?php

namespace Tests\Feature\Ads;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Tests\TestCase;

class AdsRegistryTest extends TestCase
{
    public function test_facebook_registered_when_enabled(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        $reg = app(AdsRegistry::class);
        $this->assertTrue($reg->has('facebook'));
        $this->assertInstanceOf(FacebookAdsConnector::class, $reg->for('facebook'));
    }

    public function test_absent_when_disabled(): void
    {
        config(['integrations.ads' => []]);
        $this->app->forgetInstance(AdsRegistry::class);
        $this->assertFalse(app(AdsRegistry::class)->has('facebook'));
    }
}
