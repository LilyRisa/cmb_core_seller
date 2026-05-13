<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaApiException;
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
        // "Luồng A" mặc định bật (re-fetch order detail + lấy shippingLabel qua /order/document/get).
        $this->assertTrue($c->supports('shipping.arrange'));
        $this->assertTrue($c->supports('shipping.document'));
    }

    public function test_unprocessed_raw_statuses_default(): void
    {
        // Lazada item-level: đơn chưa rời kho ⇒ pending/topack/ready_to_ship/packed.
        $statuses = $this->connector()->unprocessedRawStatuses();
        $this->assertSame(['pending', 'topack', 'ready_to_ship', 'packed'], $statuses);
    }

    public function test_unprocessed_raw_statuses_overridable_via_config(): void
    {
        config(['integrations.lazada.unprocessed_raw_statuses' => ['pending', 'ready_to_ship']]);
        $statuses = $this->connector()->unprocessedRawStatuses();
        $this->assertSame(['pending', 'ready_to_ship'], $statuses);
    }

    public function test_fetch_orders_filters_by_single_status(): void
    {
        // Khi caller truyền statuses=[X], connector pass `status=X` lên /orders/get.
        Http::fake([
            '*/orders/items/get*' => Http::response($this->ok([])),
            '*/orders/get*' => Http::response($this->ok(['count' => 0, 'orders' => []])),
        ]);

        $this->connector()->fetchOrders($this->auth(), ['statuses' => ['ready_to_ship']]);

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/orders/get')) {
                return false;
            }

            return str_contains($req->url(), 'status=ready_to_ship');
        });
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
        // Per lazada_order.md (Lazada Support 2026-05-14): 3 tab app khớp 3 trạng thái Lazada như sau —
        //   "Chờ xử lý"    ← paid (order) / pending|topack (item)
        //   "Đang xử lý"   ← packed (sau /order/fulfill/pack — chưa /order/rts)
        //   "Chờ bàn giao" ← ready_to_ship (sau /order/rts — chờ 3PL tới lấy)
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('paid'));
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('pending'));
        $this->assertSame(StandardOrderStatus::Pending, $c->mapStatus('topack'));
        $this->assertSame(StandardOrderStatus::Processing, $c->mapStatus('packed'));
        $this->assertSame(StandardOrderStatus::ReadyToShip, $c->mapStatus('ready_to_ship'));
        $this->assertSame(StandardOrderStatus::ReadyToShip, $c->mapStatus('toship'));
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

    public function test_fulfillment_throws_when_disabled(): void
    {
        config(['integrations.lazada.fulfillment_enabled' => false]);
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->arrangeShipment($this->auth(), '1001');
    }

    public function test_arrange_shipment_short_circuits_when_already_packed(): void
    {
        // Idempotent: đơn đã có tracking_code (vd seller pack ngoài app, hoặc đã arrange trước đó) ⇒
        // chỉ re-fetch & trả tracking, KHÔNG gọi /order/pack hay /order/rts.
        Http::fake([
            '*/order/get*' => Http::response($this->ok([
                'order_id' => 1001, 'price' => '220000.00', 'shipping_fee' => '20000.00', 'payment_method' => 'COD',
                'statuses' => ['packed'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
            ])),
            '*/order/items/get*' => Http::response($this->ok([
                ['order_item_id' => 9001, 'sku' => 'AO-DEN-M', 'name' => 'Áo', 'item_price' => 100000, 'status' => 'packed', 'package_id' => 'PKG1', 'tracking_code' => 'TRK-LZD-001', 'shipment_provider' => 'GHN'],
            ])),
        ]);

        $r = $this->connector()->arrangeShipment($this->auth(), '1001');
        $this->assertSame('TRK-LZD-001', $r['tracking_no']);
        $this->assertSame('GHN', $r['carrier']);
        $this->assertSame('PKG1', $r['package_id']);

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/pack'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/rts'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/shipment/providers/get'));
    }

    public function test_arrange_shipment_only_packs_at_chuan_bi_hang_does_not_call_rts(): void
    {
        // Per lazada_order.md: "Chuẩn bị hàng" = paid → packed (/order/fulfill/pack); KHÔNG gọi /order/rts.
        // /order/rts được tách sang `pushReadyToShip()` — chạy ở bước "Đã gói & sẵn sàng bàn giao".
        // raw_status trả về là 'packed' ⇒ app vào "Đang xử lý" (processing).
        Http::fake([
            '*/order/get*' => Http::response($this->ok([
                'order_id' => 1001, 'price' => '220000.00', 'shipping_fee' => '20000.00', 'payment_method' => 'COD',
                'statuses' => ['pending'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
            ])),
            '*/order/items/get*' => Http::response($this->ok([
                ['order_item_id' => 9001, 'sku' => 'AO-DEN-M', 'name' => 'Áo', 'item_price' => 100000, 'status' => 'pending'],
                ['order_item_id' => 9002, 'sku' => 'AO-DEN-L', 'name' => 'Áo', 'item_price' => 110000, 'status' => 'pending'],
            ])),
            '*/shipment/providers/get*' => Http::response($this->ok([
                'shipment_providers' => [
                    ['name' => 'Lazada Express', 'is_default' => false, 'delivery_type' => 'dropship'],
                    ['name' => 'GHN', 'is_default' => true, 'delivery_type' => 'dropship'],
                ],
            ])),
            '*/order/pack*' => Http::response($this->ok([
                'order_items' => [
                    ['tracking_number' => 'TRK-LZD-NEW-1', 'package_id' => 'PKG-NEW', 'shipment_provider' => 'GHN', 'order_item_id' => 9001],
                    ['tracking_number' => 'TRK-LZD-NEW-1', 'package_id' => 'PKG-NEW', 'shipment_provider' => 'GHN', 'order_item_id' => 9002],
                ],
            ])),
            // /order/rts response — should NOT be hit at arrangeShipment.
            '*/order/rts*' => Http::response($this->ok([])),
        ]);

        $r = $this->connector()->arrangeShipment($this->auth(), '1001');
        $this->assertSame('TRK-LZD-NEW-1', $r['tracking_no']);
        $this->assertSame('GHN', $r['carrier']);
        $this->assertSame('PKG-NEW', $r['package_id']);
        $this->assertSame('packed', $r['raw_status'], 'arrangeShipment must return packed (NOT ready_to_ship) — /order/rts is a separate step.');
        $this->assertSame([9001, 9002], $r['external_item_ids'], 'order_item_ids returned so ShipmentService can persist on shipment.raw and pass to pushReadyToShip later.');

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/order/pack')) {
                return false;
            }
            $body = (string) $req->body();

            return str_contains($body, 'dropship') && str_contains($body, 'GHN')
                && str_contains($body, '9001') && str_contains($body, '9002');
        });
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/rts'));
    }

    public function test_push_ready_to_ship_calls_rts_with_tracking_and_item_ids(): void
    {
        // "Đã gói & sẵn sàng bàn giao" trên Lazada ⇒ ShipmentService::markPacked → connector→pushReadyToShip
        // → /order/rts (delivery_type, shipment_provider, tracking_number, order_item_ids).
        Http::fake([
            '*/order/rts*' => Http::response($this->ok([])),
        ]);

        $r = $this->connector()->pushReadyToShip($this->auth(), '1001', [
            'tracking_no' => 'TRK-LZD-1', 'shipment_provider' => 'GHN', 'external_item_ids' => [9001, 9002],
            'packageId' => 'PKG-1',
        ]);
        $this->assertSame('ready_to_ship', $r['raw_status']);
        $this->assertSame('GHN', $r['carrier']);
        $this->assertSame('TRK-LZD-1', $r['tracking_no']);

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/order/rts')) {
                return false;
            }
            $body = (string) $req->body();

            return str_contains($body, 'TRK-LZD-1') && str_contains($body, 'GHN')
                && str_contains($body, '9001') && str_contains($body, '9002');
        });
    }

    public function test_push_ready_to_ship_throws_when_tracking_or_provider_missing(): void
    {
        $this->expectException(\CMBcoreSeller\Integrations\Channels\Lazada\LazadaApiException::class);
        $this->expectExceptionMessageMatches('/tracking_no|shipment_provider/i');
        $this->connector()->pushReadyToShip($this->auth(), '1001', ['external_item_ids' => [9001]]);
    }

    public function test_arrange_shipment_uses_default_provider_from_config(): void
    {
        // Config `default_shipment_provider` ⇒ bỏ qua /shipment/providers/get.
        config(['integrations.lazada.default_shipment_provider' => 'Lazada Express VN']);
        Http::fake([
            '*/order/get*' => Http::response($this->ok([
                'order_id' => 1002, 'price' => '100000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                'statuses' => ['pending'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
            ])),
            '*/order/items/get*' => Http::response($this->ok([
                ['order_item_id' => 7001, 'sku' => 'X', 'name' => 'X', 'item_price' => 100000, 'status' => 'pending'],
            ])),
            '*/order/pack*' => Http::response($this->ok([
                'tracking_number' => 'TRK-X', 'package_id' => 'PKG-X', 'shipment_provider' => 'Lazada Express VN',
            ])),
            '*/order/rts*' => Http::response($this->ok([])),
        ]);

        $r = $this->connector()->arrangeShipment($this->auth(), '1002');
        $this->assertSame('TRK-X', $r['tracking_no']);
        $this->assertSame('Lazada Express VN', $r['carrier']);

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/shipment/providers/get'));
    }

    public function test_arrange_shipment_refetch_only_mode_skips_pack_and_rts(): void
    {
        // Legacy mode cho shop không có permission Fulfillment — chỉ re-fetch, không pack/rts.
        config(['integrations.lazada.fulfillment_mode' => 'refetch_only']);
        Http::fake([
            '*/order/get*' => Http::response($this->ok([
                'order_id' => 1003, 'price' => '100000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                'statuses' => ['pending'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
            ])),
            '*/order/items/get*' => Http::response($this->ok([
                ['order_item_id' => 7001, 'sku' => 'X', 'name' => 'X', 'item_price' => 100000, 'status' => 'pending'],
            ])),
        ]);

        $r = $this->connector()->arrangeShipment($this->auth(), '1003');
        $this->assertNull($r['tracking_no']);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/pack'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/rts'));
    }

    public function test_arrange_shipment_fails_when_pack_returns_no_tracking(): void
    {
        config(['integrations.lazada.default_shipment_provider' => 'GHN']);
        Http::fake([
            '*/order/get*' => Http::response($this->ok([
                'order_id' => 1004, 'price' => '100000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                'statuses' => ['pending'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
            ])),
            '*/order/items/get*' => Http::response($this->ok([
                ['order_item_id' => 7001, 'sku' => 'X', 'name' => 'X', 'item_price' => 100000, 'status' => 'pending'],
            ])),
            '*/order/pack*' => Http::response($this->ok(['order_items' => [['order_item_id' => 7001]]])),   // không có tracking
        ]);

        $this->expectException(LazadaApiException::class);
        $this->expectExceptionMessage('không trả tracking_number');
        $this->connector()->arrangeShipment($this->auth(), '1004');
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/rts'));
    }

    public function test_arrange_shipment_only_packs_packable_items(): void
    {
        // Đơn 1005 có 3 item: 1 pending (pack được) + 1 canceled + 1 unpaid. Chỉ item pending được pack.
        config(['integrations.lazada.default_shipment_provider' => 'GHN']);
        Http::fake([
            '*/order/get*' => Http::response($this->ok([
                'order_id' => 1005, 'price' => '300000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                'statuses' => ['pending', 'canceled', 'unpaid'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
            ])),
            '*/order/items/get*' => Http::response($this->ok([
                ['order_item_id' => 8001, 'sku' => 'A', 'name' => 'A', 'item_price' => 100000, 'status' => 'pending'],
                ['order_item_id' => 8002, 'sku' => 'B', 'name' => 'B', 'item_price' => 100000, 'status' => 'canceled'],
                ['order_item_id' => 8003, 'sku' => 'C', 'name' => 'C', 'item_price' => 100000, 'status' => 'unpaid'],
            ])),
            '*/order/pack*' => Http::response($this->ok([
                'order_items' => [['order_item_id' => 8001, 'tracking_number' => 'TRK-8001', 'package_id' => 'PKG-8001', 'shipment_provider' => 'GHN']],
            ])),
            '*/order/rts*' => Http::response($this->ok([])),
        ]);

        $r = $this->connector()->arrangeShipment($this->auth(), '1005');
        $this->assertSame('TRK-8001', $r['tracking_no']);

        // Pack chỉ chứa 8001, KHÔNG có 8002/8003.
        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/order/pack')) {
                return false;
            }
            $body = (string) $req->body();

            return str_contains($body, '8001') && ! str_contains($body, '8002') && ! str_contains($body, '8003');
        });
    }

    public function test_arrange_shipment_throws_friendly_error_when_no_packable_items(): void
    {
        // Toàn bộ item đã packed/shipped/canceled ⇒ không pack được — báo lỗi rõ ràng cho user.
        Http::fake([
            '*/order/get*' => Http::response($this->ok([
                'order_id' => 1006, 'price' => '100000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                'statuses' => ['shipped'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
            ])),
            '*/order/items/get*' => Http::response($this->ok([
                // item shipped — không có tracking_code ⇒ extractExistingShipmentFromDetail trả null ⇒ chạy filter
                ['order_item_id' => 9001, 'sku' => 'X', 'name' => 'X', 'item_price' => 100000, 'status' => 'shipped'],
                ['order_item_id' => 9002, 'sku' => 'Y', 'name' => 'Y', 'item_price' => 100000, 'status' => 'canceled'],
            ])),
        ]);

        try {
            $this->connector()->arrangeShipment($this->auth(), '1006');
            $this->fail('Mong đợi LazadaApiException NoPackableItems');
        } catch (LazadaApiException $e) {
            $this->assertSame('NoPackableItems', $e->lazadaCode);
            $this->assertStringContainsString('không có item nào ở trạng thái pending', $e->getMessage());
            // message phải liệt kê các status của item để user hiểu
            $this->assertMatchesRegularExpression('/shipped|canceled/u', $e->getMessage());
        }

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/pack'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/rts'));
    }

    public function test_arrange_shipment_recovers_from_invalid_item_id_via_refetch(): void
    {
        // Race condition: lúc /order/items/get đầu thấy item pending → gọi pack → Lazada bảo "code 20
        // Invalid Order Item ID" (items vừa được pack ở nguồn khác). Code phải re-fetch & short-circuit
        // với tracking đã có thay vì throw.
        config(['integrations.lazada.default_shipment_provider' => 'GHN']);

        // Lazada API responses theo thứ tự gọi:
        //   call 1: /order/get   → đơn pending, packages=[]
        //   call 2: /order/items/get → 1 item pending
        //   call 3: /order/pack  → code 20 (đơn vừa được pack song song)
        //   call 4: /order/get   (retry refetch) → packages có tracking
        //   call 5: /order/items/get (retry refetch) → item đã có tracking_code
        Http::fakeSequence()
            ->push($this->ok(['order_id' => 1007, 'price' => '100000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                'statuses' => ['pending'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam']]))
            ->push($this->ok([['order_item_id' => 7777, 'sku' => 'X', 'name' => 'X', 'item_price' => 100000, 'status' => 'pending']]))
            ->push(['code' => '20', 'type' => 'ISP', 'message' => 'Invalid Order Item ID', 'request_id' => 'rq', 'data' => []])
            ->push($this->ok(['order_id' => 1007, 'price' => '100000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                'statuses' => ['packed'], 'created_at' => '2026-05-17 10:00:00 +0700', 'updated_at' => '2026-05-17 11:00:00 +0700',
                'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam']]))
            ->push($this->ok([['order_item_id' => 7777, 'sku' => 'X', 'name' => 'X', 'item_price' => 100000, 'status' => 'packed', 'package_id' => 'PKG-RACE', 'tracking_code' => 'TRK-RACE-OK', 'shipment_provider' => 'GHN']]));

        $r = $this->connector()->arrangeShipment($this->auth(), '1007');
        $this->assertSame('TRK-RACE-OK', $r['tracking_no']);
        $this->assertSame('PKG-RACE', $r['package_id']);

        // Không gọi RTS — items đã được pack ở nguồn khác, sàn tự lo phần còn lại.
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/rts'));
    }

    public function test_get_shipping_document_fetches_base64_pdf(): void
    {
        $pdfBytes = "%PDF-1.4\nfake-pdf-bytes";
        Http::fake([
            '*/order/document/get*' => Http::response($this->ok(['document' => ['file' => base64_encode($pdfBytes), 'doc_type' => 'shippingLabel']])),
        ]);
        $doc = $this->connector()->getShippingDocument($this->auth(), '1001', ['order_item_ids' => [9001]]);
        $this->assertSame($pdfBytes, $doc['bytes']);
        $this->assertSame('application/pdf', $doc['mime']);
    }

    public function test_get_shipping_document_prefers_print_awb_when_package_id_known(): void
    {
        // VN region: when `externalPackageId` is passed (returned from /order/pack), connector should hit
        // `/order/package/document/get` (PrintAWB) first. PrintAWB nhận MỘT business param `getDocumentReq`
        // = JSON({doc_type:PDF, packages:[{package_id}], print_item_list:true}). Gửi flat sẽ trả
        // `MissingParameter "getDocumentReq"`.
        $pdfBytes = "%PDF-1.4\nprint-awb-bytes";
        Http::fake([
            '*/order/package/document/get*' => Http::response($this->ok(['document' => ['file' => base64_encode($pdfBytes), 'doc_type' => 'shippingLabel']])),
            '*/order/document/get*' => Http::response($this->ok([])),
        ]);
        $doc = $this->connector()->getShippingDocument($this->auth(), '1001', [
            'externalPackageId' => 'PKG-99', 'order_item_ids' => [9001],
        ]);
        $this->assertSame($pdfBytes, $doc['bytes']);
        // Verify PrintAWB endpoint was hit AND request carries the wrapped `getDocumentReq` JSON envelope.
        Http::assertSent(function ($req) {
            if (! str_contains((string) $req->url(), '/order/package/document/get')) {
                return false;
            }
            parse_str((string) parse_url((string) $req->url(), PHP_URL_QUERY), $q);
            $envelope = isset($q['getDocumentReq']) ? json_decode((string) $q['getDocumentReq'], true) : null;

            return is_array($envelope)
                && ($envelope['doc_type'] ?? null) === 'PDF'
                && ($envelope['packages'][0]['package_id'] ?? null) === 'PKG-99'
                && ($envelope['print_item_list'] ?? null) === true;
        });
    }

    public function test_get_shipping_document_falls_back_to_legacy_when_print_awb_returns_empty(): void
    {
        // Legacy endpoint must be tried as fallback when PrintAWB returns empty file (some legacy SoC
        // shops don't have permission on /order/package/document/get even after /order/pack returns
        // package_id). Connector should keep trying until it gets bytes.
        $pdfBytes = "%PDF-1.4\nlegacy-bytes";
        Http::fake([
            '*/order/package/document/get*' => Http::response($this->ok(['document' => ['file' => '']])),
            '*/order/document/get*' => Http::response($this->ok(['document' => ['file' => base64_encode($pdfBytes)]])),
        ]);
        $doc = $this->connector()->getShippingDocument($this->auth(), '1001', [
            'externalPackageId' => 'PKG-99', 'order_item_ids' => [9001],
        ]);
        $this->assertSame($pdfBytes, $doc['bytes']);
    }

    public function test_get_shipping_document_throws_when_both_endpoints_return_empty(): void
    {
        // ShipmentService wraps this in a sync-retry loop + async FetchChannelLabel job (Lazada 3PL renders
        // PDF async 5–30s+ after /order/rts). The connector itself just surfaces the failure so the caller
        // can decide to retry.
        Http::fake([
            '*/order/package/document/get*' => Http::response($this->ok(['document' => ['file' => '']])),
            '*/order/document/get*' => Http::response($this->ok(['document' => ['file' => '']])),
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/chưa cấp tệp/i');
        $this->connector()->getShippingDocument($this->auth(), '1001', [
            'externalPackageId' => 'PKG-99', 'order_item_ids' => [9001],
        ]);
    }
}
