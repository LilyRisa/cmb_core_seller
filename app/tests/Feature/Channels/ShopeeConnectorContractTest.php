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

    public function test_fetch_orders_sends_order_status_filter(): void
    {
        Http::fake([
            '*/api/v2/order/get_order_list*' => Http::response(ShopeeFixtures::orderList('', false), 200),
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->connector()->fetchOrders($auth, [
            'updatedFrom' => \Carbon\CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => \Carbon\CarbonImmutable::parse('2026-01-05T00:00:00Z'),
            'statuses' => ['READY_TO_SHIP'],
        ]);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/order/get_order_list') && str_contains($r->url(), 'order_status=READY_TO_SHIP'));
    }

    private function signedPush(array $body): \Illuminate\Http\Request
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $pushUrl = 'https://partner.test-stable.shopeemobile.com/webhook/shopee';
        config(['integrations.shopee.push_url' => $pushUrl]);
        $sign = hash_hmac('sha256', $pushUrl.'|'.$raw, 'PARTNER_KEY');
        $req = \Illuminate\Http\Request::create($pushUrl, 'POST', content: $raw);
        $req->headers->set('Authorization', $sign);

        return $req;
    }

    public function test_verify_webhook_signature_ok_and_reject(): void
    {
        $this->connector(); // configure
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])]);
        $this->assertTrue($this->connector()->verifyWebhookSignature($req));

        $bad = \Illuminate\Http\Request::create('https://partner.test-stable.shopeemobile.com/webhook/shopee', 'POST', content: '{}');
        $bad->headers->set('Authorization', 'deadbeef');
        $this->assertFalse($this->connector()->verifyWebhookSignature($bad));
    }

    public function test_parse_webhook_order_status_update(): void
    {
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])]);
        $evt = $this->connector()->parseWebhook($req);

        $this->assertSame('order_status_update', $evt->type);
        $this->assertSame('55', $evt->externalShopId);
        $this->assertSame('SN_9', $evt->externalOrderId);
        $this->assertSame('READY_TO_SHIP', $evt->orderRawStatus);
    }

    public function test_parse_webhook_deauthorized(): void
    {
        $req = $this->signedPush(['code' => 1, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['success' => 1])]);
        $this->assertSame('shop_deauthorized', $this->connector()->parseWebhook($req)->type);
    }

    public function test_arrange_shipment_ships_and_returns_tracking(): void
    {
        Http::fake([
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $res = $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [['externalPackageId' => 'PKG_1']]]);
        $this->assertSame('TRK123', $res['tracking_no']);
        $this->assertSame('PROCESSED', $res['raw_status']);
    }

    public function test_get_shipping_document_polls_then_downloads(): void
    {
        Http::fake([
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFixtures::createDocument(), 200),
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFixtures::documentResult('READY'), 200),
            '*/api/v2/logistics/download_shipping_document*' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $doc = $this->connector()->getShippingDocument($auth, 'SN_1', ['externalPackageId' => 'PKG_1']);
        $this->assertSame('application/pdf', $doc['mime']);
        $this->assertStringContainsString('%PDF', $doc['bytes']);
        $this->assertStringEndsWith('.pdf', $doc['filename']);
    }

    public function test_get_shipping_document_failed_throws(): void
    {
        config(['integrations.shopee.document_poll_attempts' => 2, 'integrations.shopee.document_poll_sleep_ms' => 0]);
        Http::fake([
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFixtures::createDocument(), 200),
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFixtures::documentResult('FAILED'), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->expectException(\CMBcoreSeller\Integrations\Channels\Shopee\ShopeeApiException::class);
        $this->connector()->getShippingDocument($auth, 'SN_1', ['externalPackageId' => 'PKG_1']);
    }

    public function test_push_ready_to_ship_unsupported(): void
    {
        $this->expectException(\CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation::class);
        $this->connector()->pushReadyToShip(new AuthContext(1, 'shopee', '55', 'ACCESS_1'), 'SN_1');
    }

    public function test_fetch_listings_returns_one_entry_per_model(): void
    {
        Http::fake([
            '*/api/v2/product/get_item_list*' => Http::response(ShopeeFixtures::itemList(), 200),
            '*/api/v2/product/get_item_base_info*' => Http::response(ShopeeFixtures::itemBaseInfo(), 200),
            '*/api/v2/product/get_model_list*' => Http::response(ShopeeFixtures::modelList(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchListings($auth, ['pageSize' => 50]);
        $this->assertCount(1, $page->items);
        $l = $page->items[0];
        $this->assertSame('SKU-A-RED', $l->externalSkuId);
        $this->assertSame('111', $l->externalProductId);
        $this->assertSame(115000, $l->price);
        $this->assertSame(7, $l->channelStock);
        $this->assertTrue($l->isActive);
    }

    public function test_update_stock_posts_model_stock(): void
    {
        Http::fake(['*/api/v2/product/update_stock*' => Http::response(['error' => '', 'response' => []], 200)]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->connector()->updateStock($auth, '222', 9, ['external_product_id' => '111']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/product/update_stock')
            && $r['item_id'] === 111
            && $r['stock_list'][0]['model_id'] === 222
            && $r['stock_list'][0]['seller_stock'][0]['stock'] === 9);
    }
}
