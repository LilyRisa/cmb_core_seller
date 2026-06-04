<?php

namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsOAuthTest extends TestCase
{
    private function conn(): FacebookAdsConnector
    {
        return new FacebookAdsConnector([
            'app_id' => 'APPID', 'app_secret' => 'SEC', 'graph_version' => 'v19.0',
            'redirect_uri' => 'https://app.cmbcore.com/oauth/facebook_ads/callback',
            'scopes' => 'ads_read,business_management',
        ]);
    }

    public function test_authorize_url(): void
    {
        $u = $this->conn()->buildAuthorizationUrl('ST');
        $this->assertStringContainsString('client_id=APPID', $u);
        $this->assertStringContainsString('state=ST', $u);
        $this->assertStringContainsString('ads_read', $u);
        $this->assertStringContainsString('oauth%2Ffacebook_ads%2Fcallback', $u);
    }

    public function test_exchange_code(): void
    {
        Http::fake(['graph.facebook.com/*oauth/access_token*' => Http::response(['access_token' => 'AT', 'expires_in' => 5184000], 200)]);
        $t = $this->conn()->exchangeCodeForToken('CODE');
        $this->assertSame('AT', $t['access_token']);
        $this->assertNotNull($t['expires_at']);
    }
}
