<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaSigner;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contract test for LazadaConnector: feed it sample Lazada Open Platform responses
 * (Http::fake, no real network) and assert it returns the standard DTOs.
 * See docs/04-channels/lazada.md, SPEC 0008, docs/09-process/testing-strategy.md.
 */
class LazadaConnectorContractTest extends TestCase
{
    private const APP_KEY = 'lzd_key';

    private const APP_SECRET = 'lzd_secret';

    private const SHOP_ID = 'VNSHOP01';

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.lazada.app_key' => self::APP_KEY, 'integrations.lazada.app_secret' => self::APP_SECRET]);
        // lazada is env-gated (INTEGRATIONS_CHANNELS) — register it for the test.
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);
    }

    private function connector(): ChannelConnector
    {
        return app(ChannelRegistry::class)->for('lazada');
    }

    private function auth(): AuthContext
    {
        return new AuthContext(channelAccountId: 1, provider: 'lazada', externalShopId: self::SHOP_ID, accessToken: 'at');
    }

    // ---- envelope helpers --------------------------------------------------

    /** @param array<string,mixed> $data */
    private function ok(array $data): array
    {
        return ['code' => '0', 'type' => '', 'request_id' => 'rq', 'data' => $data];
    }

    // ---- tests -------------------------------------------------------------

    public function test_registry_resolves_lazada_connector(): void
    {
        $c = $this->connector();
        $this->assertSame('lazada', $c->code());
        $this->assertSame('Lazada', $c->displayName());
        $this->assertTrue($c->supports('orders.fetch'));
        $this->assertTrue($c->supports('listings.updateStock'));
        $this->assertFalse($c->supports('shipping.arrange'));
    }

    public function test_signer_is_deterministic_and_order_independent(): void
    {
        $a = LazadaSigner::sign(self::APP_SECRET, '/orders/get', ['app_key' => 'k', 'timestamp' => '123', 'b' => '2', 'a' => '1']);
        $b = LazadaSigner::sign(self::APP_SECRET, '/orders/get', ['a' => '1', 'b' => '2', 'timestamp' => '123', 'app_key' => 'k', 'sign' => 'IGNORED']);
        $this->assertSame($a, $b);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{64}$/', $a);
        // a fixed vector
        $expected = strtoupper(hash_hmac('sha256', '/orders/geta1app_keyk'.'b2'.'timestamp123', self::APP_SECRET));
        // note: keys sorted ascending -> a, app_key, b, timestamp
        $this->assertSame($expected, LazadaSigner::sign(self::APP_SECRET, '/orders/get', ['a' => '1', 'app_key' => 'k', 'b' => '2', 'timestamp' => '123']));
    }

    public function test_exchange_and_refresh_token(): void
    {
        Http::fake([
            '*/auth/token/create*' => Http::response(['access_token' => 'AT1', 'refresh_token' => 'RT1', 'expires_in' => 604800, 'refresh_expires_in' => 2592000, 'account' => 'seller@x.vn', 'country' => 'vn', 'country_user_info' => [['country' => 'vn', 'seller_id' => '123', 'short_code' => self::SHOP_ID]]]),
            '*/auth/token/refresh*' => Http::response(['access_token' => 'AT2', 'refresh_token' => 'RT2', 'expires_in' => 604800]),
        ]);

        $t1 = $this->connector()->exchangeCodeForToken('CODE');
        $this->assertSame('AT1', $t1->accessToken);
        $this->assertSame('RT1', $t1->refreshToken);
        $this->assertNotNull($t1->expiresAt);
        $this->assertTrue($t1->expiresAt->isFuture());
        $this->assertSame('vn,seller@x.vn', $t1->scope);
        $this->assertSame(self::SHOP_ID, $t1->raw['country_user_info'][0]['short_code']);

        $t2 = $this->connector()->refreshToken('RT1');
        $this->assertSame('AT2', $t2->accessToken);
    }

    public function test_fetch_shop_info(): void
    {
        Http::fake(['*/seller/get*' => Http::response($this->ok(['name' => 'Shop Lazada Test', 'short_code' => 'ABC123', 'seller_id' => self::SHOP_ID, 'location' => 'Vietnam']))]);

        $shop = $this->connector()->fetchShopInfo($this->auth());
        // Ưu tiên seller_id (numeric) → khớp với `data.seller_id` mà webhook push gửi sau này.
        $this->assertSame(self::SHOP_ID, $shop->externalShopId);
        $this->assertSame('ABC123', $shop->raw['seller']['short_code']);
        $this->assertSame('Shop Lazada Test', $shop->name);
        $this->assertSame('VN', $shop->region);
    }

    public function test_fetch_orders_with_items_money_and_status(): void
    {
        Http::fake([
            '*/orders/items/get*' => Http::response($this->ok([
                ['order_id' => 1001, 'order_items' => [
                    ['order_item_id' => 9001, 'sku' => 'AO-DEN-M', 'shop_sku' => 'shopsku-1', 'name' => 'Áo thun đen', 'variation' => 'Size:M', 'item_price' => 100000.00, 'paid_price' => 99000.00, 'voucher_amount' => 1000.00, 'status' => 'pending', 'product_main_image' => 'https://img/1.jpg', 'package_id' => 'PKG1', 'tracking_code' => '', 'shipment_provider' => ''],
                    ['order_item_id' => 9002, 'sku' => 'AO-DEN-M', 'shop_sku' => 'shopsku-1', 'name' => 'Áo thun đen', 'variation' => 'Size:M', 'item_price' => 100000.00, 'paid_price' => 99000.00, 'voucher_amount' => 1000.00, 'status' => 'pending', 'package_id' => 'PKG1'],
                ]],
            ])),
            '*/orders/get*' => Http::response($this->ok([
                'count' => 1,
                'orders' => [[
                    'order_id' => 1001, 'order_number' => 555111, 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                    'price' => '220000.00', 'shipping_fee' => '20000.00', 'voucher' => '2000.00', 'voucher_seller' => '2000.00',
                    'payment_method' => 'COD', 'statuses' => ['pending'], 'items_count' => 2,
                    'customer_first_name' => 'Trần', 'customer_last_name' => 'Bình',
                    'address_shipping' => ['first_name' => 'Trần', 'last_name' => 'Bình', 'phone' => '0900111222', 'address1' => 'Số 10 Lê Lợi', 'address3' => 'Phường Bến Nghé', 'address4' => 'Quận 1', 'city' => 'Hồ Chí Minh', 'country' => 'Vietnam', 'post_code' => '70000'],
                    'buyer_note' => 'Giao giờ hành chính',
                ]],
            ])),
        ]);

        $page = $this->connector()->fetchOrders($this->auth(), ['updatedFrom' => now()->subDay()]);
        $this->assertCount(1, $page->items);
        $o = $page->items[0];
        $this->assertSame('1001', $o->externalOrderId);
        $this->assertSame('lazada', $o->source);
        $this->assertSame('555111', $o->orderNumber);
        $this->assertSame('pending', $o->rawStatus);
        $this->assertSame('Trần Bình', $o->buyer['name']);
        $this->assertSame('Hồ Chí Minh', $o->shippingAddress['province']);
        $this->assertSame('Quận 1', $o->shippingAddress['district']);
        $this->assertSame('Phường Bến Nghé', $o->shippingAddress['ward']);
        $this->assertSame('Giao giờ hành chính', $o->shippingAddress['note']);
        // money: VND đồng, no decimals
        $this->assertSame(220000, $o->grandTotal);
        $this->assertSame(20000, $o->shippingFee);
        $this->assertSame(2000, $o->sellerDiscount);
        $this->assertSame(200000, $o->itemTotal);   // 2 items × 100000
        $this->assertTrue($o->isCod);
        $this->assertSame(220000, $o->codAmount);
        // 2 item rows of the same SKU -> one line, quantity 2
        $this->assertCount(1, $o->items);
        $this->assertSame('AO-DEN-M', $o->items[0]->sellerSku);
        $this->assertSame(2, $o->items[0]->quantity);
        $this->assertSame(100000, $o->items[0]->unitPrice);
        $this->assertSame('Size:M', $o->items[0]->variation);
        // package carried through (deduped by package_id)
        $this->assertCount(1, $o->packages);
        $this->assertSame('PKG1', $o->packages[0]['externalPackageId']);
    }

    public function test_fetch_order_detail(): void
    {
        Http::fake([
            '*/order/get*' => Http::response($this->ok(['order_id' => 2002, 'order_number' => 888, 'price' => '50000.00', 'shipping_fee' => '0.00', 'payment_method' => 'Credit Card', 'statuses' => ['shipped'], 'created_at' => '2026-05-16 09:00:00 +0700', 'updated_at' => '2026-05-17 09:00:00 +0700', 'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam']])),
            '*/order/items/get*' => Http::response($this->ok([
                ['order_item_id' => 1, 'sku' => 'SKU-X', 'name' => 'Sản phẩm X', 'item_price' => 50000.00, 'status' => 'shipped', 'tracking_code' => 'LEX99', 'shipment_provider' => 'Lazada Express', 'package_id' => 'P9', 'updated_at' => '2026-05-17 09:00:00 +0700'],
            ])),
        ]);

        $o = $this->connector()->fetchOrderDetail($this->auth(), '2002');
        $this->assertSame('2002', $o->externalOrderId);
        $this->assertSame('shipped', $o->rawStatus);
        $this->assertFalse($o->isCod);
        $this->assertCount(1, $o->items);
        $this->assertSame('SKU-X', $o->items[0]->sellerSku);
        $this->assertSame('LEX99', $o->packages[0]['trackingNo']);
        $this->assertNotNull($o->shippedAt);
    }

    public function test_status_mapping(): void
    {
        $c = $this->connector();
        $this->assertSame(StandardOrderStatus::Unpaid, $c->mapStatus('unpaid'));
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('pending'));
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('topack'));
        // SPEC 0013: packed / ready_to_ship = đã RTS/in phiếu ⇒ processing (xử lý nội bộ)
        $this->assertSame(StandardOrderStatus::Processing, $c->mapStatus('packed'));
        $this->assertSame(StandardOrderStatus::Processing, $c->mapStatus('ready_to_ship'));
        $this->assertSame(StandardOrderStatus::Shipped, $c->mapStatus('shipped'));
        $this->assertSame(StandardOrderStatus::Delivered, $c->mapStatus('delivered'));
        $this->assertSame(StandardOrderStatus::DeliveryFailed, $c->mapStatus('failed'));
        $this->assertSame(StandardOrderStatus::ReturnedRefunded, $c->mapStatus('returned'));
        $this->assertSame(StandardOrderStatus::Cancelled, $c->mapStatus('canceled'));
        // item-level statuses collapsed via the raw order's `statuses` list
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('shipped', ['statuses' => ['pending', 'shipped']]));   // one item still pending
        $this->assertSame(StandardOrderStatus::Cancelled, $c->mapStatus('shipped', ['statuses' => ['canceled', 'canceled']]));
        // unknown raw status -> never throws
        $this->assertSame(StandardOrderStatus::Cancelled, $c->mapStatus('weird_cancel_x'));
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('totally_new'));
    }

    public function test_fetch_listings(): void
    {
        Http::fake(['*/products/get*' => Http::response($this->ok([
            'total_products' => 1,
            'products' => [[
                'item_id' => 700, 'attributes' => ['name' => 'Áo polo'],
                'skus' => [
                    ['SkuId' => 'sku-a', 'ShopSku' => 'shop-a', 'SellerSku' => 'POLO-DEN-M', 'quantity' => 12, 'price' => '150000.00', 'special_price' => '129000.00', 'Status' => 'active', 'Images' => ['https://img/p1.jpg'], 'size' => 'M', 'color_family' => 'Đen'],
                    ['SkuId' => 'sku-b', 'ShopSku' => 'shop-b', 'SellerSku' => 'POLO-DEN-L', 'quantity' => 0, 'price' => '150000.00', 'Status' => 'inactive', 'size' => 'L'],
                ],
            ]],
        ]))]);

        $page = $this->connector()->fetchListings($this->auth());
        $this->assertCount(2, $page->items);
        $a = $page->items[0];
        $this->assertSame('shop-a', $a->externalSkuId);
        $this->assertSame('700', $a->externalProductId);
        $this->assertSame('POLO-DEN-M', $a->sellerSku);
        $this->assertSame('Áo polo', $a->title);
        $this->assertSame(129000, $a->price);   // special_price preferred
        $this->assertSame(12, $a->channelStock);
        $this->assertTrue($a->isActive);
        $this->assertSame('https://img/p1.jpg', $a->image);
        $this->assertFalse($page->items[1]->isActive);   // inactive
    }

    public function test_update_stock_sends_payload(): void
    {
        Http::fake(['*/product/price_quantity/update*' => Http::response($this->ok([]))]);

        $this->connector()->updateStock($this->auth(), 'shop-a', 7, ['seller_sku' => 'POLO-DEN-M', 'external_product_id' => '700']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/product/price_quantity/update')
                && str_contains((string) $request->body(), 'POLO-DEN-M')
                && str_contains((string) $request->body(), '7');
        });
    }

    public function test_webhook_verify_and_parse(): void
    {
        $c = $this->connector();
        $body = json_encode(['message_type' => 0, 'timestamp' => 1778000000000, 'site' => 'lazada_vn', 'data' => ['trade_order_id' => '1001', 'trade_order_line_id' => '9001', 'order_item_status' => 'shipped', 'seller_id' => self::SHOP_ID]]);
        $sig = strtoupper(hash_hmac('sha256', $body, self::APP_SECRET));

        $valid = Request::create('/webhook/lazada', 'POST', server: ['HTTP_X_LAZOP_SIGN' => $sig], content: $body);
        $this->assertTrue($c->verifyWebhookSignature($valid));
        $tampered = Request::create('/webhook/lazada', 'POST', server: ['HTTP_X_LAZOP_SIGN' => 'deadbeef'], content: $body);
        $this->assertFalse($c->verifyWebhookSignature($tampered));
        $noSig = Request::create('/webhook/lazada', 'POST', content: $body);
        $this->assertFalse($c->verifyWebhookSignature($noSig));

        $event = $c->parseWebhook($valid);
        $this->assertSame('lazada', $event->provider);
        $this->assertSame(WebhookEventDTO::TYPE_ORDER_STATUS_UPDATE, $event->type);
        $this->assertSame('1001', $event->externalOrderId);
        $this->assertSame(self::SHOP_ID, $event->externalShopId);
        $this->assertSame('shipped', $event->orderRawStatus);
    }

    public function test_unsupported_fulfillment_ops_throw(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->arrangeShipment($this->auth(), '1001');
    }
}
