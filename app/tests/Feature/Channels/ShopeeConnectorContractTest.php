<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

class ShopeeConnectorContractTest extends TestCase
{
    private function connector(): ShopeeConnector
    {
        ShopeeFixtures::configure();
        $registry = app(ChannelRegistry::class);
        $registry->register('shopee', ShopeeConnector::class);

        return $registry->for('shopee');
    }

    public function test_build_authorization_url_signs_and_carries_state_in_redirect(): void
    {
        $url = $this->connector()->buildAuthorizationUrl('STATE123', ['redirect_uri' => 'https://app.test/oauth/shopee/callback']);

        $this->assertStringContainsString('/api/v2/shop/auth_partner', $url);
        $this->assertStringContainsString('partner_id=1001', $url);
        $this->assertStringContainsString('sign=', $url);
        $this->assertStringContainsString('state%3DSTATE123', $url); // state nested in url-encoded redirect
    }

    public function test_exchange_code_for_token_uses_shop_id_from_context(): void
    {
        Http::fake(['*/api/v2/auth/token/get*' => Http::response(ShopeeFixtures::tokenGet(), 200)]);

        $token = $this->connector()->exchangeCodeForToken('CODE', ['shop_id' => '55']);

        $this->assertSame('ACCESS_1', $token->accessToken);
        $this->assertSame('REFRESH_1', $token->refreshToken);
        $this->assertSame('55', (string) $token->raw['shop_id']);
        $this->assertNotNull($token->expiresAt);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/auth/token/get') && $r['shop_id'] === 55 && $r['code'] === 'CODE');
    }

    public function test_refresh_token_uses_shop_id_from_context(): void
    {
        Http::fake(['*/api/v2/auth/access_token/get*' => Http::response(ShopeeFixtures::tokenGet(), 200)]);

        $token = $this->connector()->refreshToken('REFRESH_1', ['shop_id' => '55']);

        $this->assertSame('ACCESS_1', $token->accessToken);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/auth/access_token/get') && $r['refresh_token'] === 'REFRESH_1' && $r['shop_id'] === 55);
    }

    public function test_fetch_shop_info_reads_shop_id_from_token_raw(): void
    {
        Http::fake(['*/api/v2/shop/get_shop_info*' => Http::response(ShopeeFixtures::shopInfo(), 200)]);

        $shop = $this->connector()->fetchShopInfo(new AuthContext(0, 'shopee', '', 'ACCESS_1', extra: ['token_raw' => ['shop_id' => 55]]));

        $this->assertSame('55', $shop->externalShopId);
        $this->assertSame('Shop Shopee VN', $shop->name);
        $this->assertSame('VN', $shop->region);
    }
}
