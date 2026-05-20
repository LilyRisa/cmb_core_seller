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

    public function test_fetch_orders_splits_15_day_windows_and_maps_detail(): void
    {
        Http::fake([
            '*/api/v2/order/get_order_list*' => Http::response(ShopeeFixtures::orderList('', false), 200),
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchOrders($auth, [
            'updatedFrom' => \Carbon\CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => \Carbon\CarbonImmutable::parse('2026-01-10T00:00:00Z'),
        ]);

        $this->assertCount(2, $page->items);
        $first = $page->items[0];
        $this->assertSame('SN_1', $first->externalOrderId);
        $this->assertSame('READY_TO_SHIP', $first->rawStatus);
        $this->assertSame('shopee', $first->source);
        $this->assertTrue($first->isCod);
        $this->assertSame(250000, $first->grandTotal);
        $this->assertSame(20000, $first->shippingFee);
        $this->assertCount(1, $first->items);
        $this->assertSame('111', $first->items[0]->externalProductId);
        $this->assertSame('SKU-A-RED', $first->items[0]->externalSkuId);
        $this->assertSame(2, $first->items[0]->quantity);
        $this->assertSame('HCM', $first->shippingAddress['province']);
    }

    public function test_fetch_orders_window_over_15_days_returns_cursor_to_continue(): void
    {
        Http::fake([
            '*/api/v2/order/get_order_list*' => Http::response(ShopeeFixtures::orderList('', false), 200),
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchOrders($auth, [
            'updatedFrom' => \Carbon\CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => \Carbon\CarbonImmutable::parse('2026-02-15T00:00:00Z'), // 45 days -> needs >1 window
        ]);

        $this->assertTrue($page->hasMore);
        $this->assertNotNull($page->nextCursor);
        $this->assertStringContainsString(':', $page->nextCursor); // encodes window + inner cursor
    }

    public function test_fetch_order_detail_maps_single_order(): void
    {
        Http::fake(['*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200)]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $order = $this->connector()->fetchOrderDetail($auth, 'SN_1');
        $this->assertSame('SN_1', $order->externalOrderId);
        $this->assertSame('READY_TO_SHIP', $order->rawStatus);
    }
}
