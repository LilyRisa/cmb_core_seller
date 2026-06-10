<?php

namespace Tests\Feature\Channels;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeApiException;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Http\Request;
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
            'updatedFrom' => CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => CarbonImmutable::parse('2026-01-10T00:00:00Z'),
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
        $this->assertSame('222', $first->items[0]->externalSkuId);
        $this->assertSame('SKU-A-RED', $first->items[0]->sellerSku);
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
            'updatedFrom' => CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => CarbonImmutable::parse('2026-02-15T00:00:00Z'), // 45 days -> needs >1 window
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
            'updatedFrom' => CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => CarbonImmutable::parse('2026-01-05T00:00:00Z'),
            'statuses' => ['READY_TO_SHIP'],
        ]);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/order/get_order_list') && str_contains($r->url(), 'order_status=READY_TO_SHIP'));
    }

    private function signedPush(array $body): Request
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $pushUrl = 'https://app.cmbcore.com/webhook/shopee';
        config(['integrations.shopee.push_url' => $pushUrl]);
        $sign = hash_hmac('sha256', $pushUrl.'|'.$raw, 'PARTNER_KEY');
        $req = Request::create($pushUrl, 'POST', content: $raw);
        $req->headers->set('Authorization', $sign);

        return $req;
    }

    public function test_verify_webhook_signature_ok_and_reject(): void
    {
        $this->connector(); // configure
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])]);
        $this->assertTrue($this->connector()->verifyWebhookSignature($req));

        $bad = Request::create('https://app.cmbcore.com/webhook/shopee', 'POST', content: '{}');
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
        // Code 2 = Shop Authorization Canceled (deauth). Code 1 = authorization GRANTED (không revoke).
        $req = $this->signedPush(['code' => 2, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['success' => 1])]);
        $this->assertSame('shop_deauthorized', $this->connector()->parseWebhook($req)->type);

        $granted = $this->signedPush(['code' => 1, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['success' => 1])]);
        $this->assertSame('unknown', $this->connector()->parseWebhook($granted)->type);
    }

    public function test_verify_uses_separate_push_partner_key_when_set(): void
    {
        // Shopee Push Mechanism cấp Push Partner Key RIÊNG với partner_key API.
        $c = $this->connector();
        $pushUrl = 'https://app.cmbcore.com/webhook/shopee';
        config(['integrations.shopee.push_url' => $pushUrl, 'integrations.shopee.push_partner_key' => 'PUSH_KEY_TEST']);
        $raw = json_encode(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])], JSON_UNESCAPED_SLASHES);

        // Ký bằng push partner key ⇒ verify OK.
        $ok = Request::create($pushUrl, 'POST', content: $raw);
        $ok->headers->set('Authorization', hash_hmac('sha256', $pushUrl.'|'.$raw, 'PUSH_KEY_TEST'));
        $this->assertTrue($c->verifyWebhookSignature($ok));

        // Ký bằng partner_key API (sai vì đã có push key riêng) ⇒ reject.
        $bad = Request::create($pushUrl, 'POST', content: $raw);
        $bad->headers->set('Authorization', hash_hmac('sha256', $pushUrl.'|'.$raw, 'PARTNER_KEY'));
        $this->assertFalse($c->verifyWebhookSignature($bad));
    }

    public function test_arrange_shipment_ships_and_returns_tracking(): void
    {
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
            // Đơn CHƯA ship: get_tracking_number trả rỗng (idempotency check) ⇒ tiến hành ship; SAU ship mới có tracking.
            // Sau ship_order, get_tracking_number trả mã (chỉ gọi 1 lần — idempotency nay dựa trên order_status,
            // KHÔNG còn dò tracking trước để short-circuit).
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $res = $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [['externalPackageId' => 'PKG_1']]]);
        $this->assertSame('TRK123', $res['tracking_no']);
        $this->assertSame('PROCESSED', $res['raw_status']);
        Http::assertSent(function (\Illuminate\Http\Client\Request $r) {
            if (! str_contains($r->url(), '/api/v2/logistics/ship_order')) {
                return false;
            }

            return array_key_exists('pickup', $r->data()) && ! array_key_exists('dropoff', $r->data());
        });
    }

    public function test_arrange_shipment_idempotent_when_already_processed(): void
    {
        // Đơn ĐÃ arrange thật (order_status = PROCESSED+) ⇒ KHÔNG gọi ship_order lại (re-call sẽ lỗi
        // "not ready to ship"); trả tracking đang có. Idempotency dựa trên ORDER_STATUS, không dựa trên tracking.
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(['error' => '', 'response' => ['order_list' => [
                ShopeeFixtures::orderRow('SN_1', 'PROCESSED'),
            ]]], 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $res = $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [['externalPackageId' => 'PKG_1']]]);

        $this->assertSame('TRK123', $res['tracking_no']);
        Http::assertNotSent(fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), '/api/v2/logistics/ship_order'));
    }

    public function test_arrange_shipment_ships_even_when_tracking_preassigned_at_ready_to_ship(): void
    {
        // ROOT CAUSE `create_shipping_document → logistics.tracking_number_invalid`: Shopee PRE-ASSIGN tracking
        // ngay ở READY_TO_SHIP (trước khi ship_order). KHÔNG được short-circuit theo tracking đó — phải ship_order
        // thật, nếu không đơn chưa arrange ⇒ tạo tem báo tracking invalid. Đây là test mô phỏng đúng bug thật.
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(['error' => '', 'response' => ['order_list' => [
                ShopeeFixtures::orderRow('SN_1', 'READY_TO_SHIP'),
            ]]], 200),
            // tracking ĐÃ có sẵn (pre-assigned) ngay khi chưa ship — cũ sẽ short-circuit & skip ship (BUG).
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $res = $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [['externalPackageId' => 'PKG_1']]]);

        $this->assertSame('TRK123', $res['tracking_no']);
        // BẮT BUỘC vẫn gọi ship_order dù tracking đã có sẵn ở READY_TO_SHIP.
        Http::assertSent(fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), '/api/v2/logistics/ship_order'));
    }

    public function test_arrange_shipment_skips_ship_when_order_already_processed(): void
    {
        // Root cause `get_shipping_parameter [error_param] ... only ... when package is ready to be shipped`:
        // đơn đã arrange trước đó (order_status = PROCESSED) nhưng Shopee chưa cấp tracking (async) ⇒
        // get_tracking_number rỗng. KHÔNG được gọi lại get_shipping_parameter/ship_order — chỉ trả raw_status
        // PROCESSED để app hiểu đơn đã xử lý (caller sẽ lấy tracking/label sau).
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(['error' => '', 'response' => ['order_list' => [
                ShopeeFixtures::orderRow('SN_7', 'PROCESSED'),
            ]]], 200),
            '*/api/v2/logistics/get_tracking_number*' => Http::response(['error' => '', 'response' => ['tracking_number' => '']], 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $res = $this->connector()->arrangeShipment($auth, 'SN_7', ['packages' => [['externalPackageId' => 'PKG_7']]]);

        $this->assertSame('PROCESSED', $res['raw_status']);
        Http::assertNotSent(fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), '/api/v2/logistics/get_shipping_parameter'));
        Http::assertNotSent(fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), '/api/v2/logistics/ship_order'));
    }

    public function test_arrange_shipment_throws_when_order_not_ready_to_ship(): void
    {
        // Đơn UNPAID (chưa thanh toán) ⇒ không thể tạo phiếu giao hàng. Báo lỗi rõ ràng, KHÔNG gọi
        // get_shipping_parameter (sẽ trả error_param khó hiểu cho user).
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(['error' => '', 'response' => ['order_list' => [
                ShopeeFixtures::orderRow('SN_8', 'UNPAID'),
            ]]], 200),
            '*/api/v2/logistics/get_tracking_number*' => Http::response(['error' => '', 'response' => ['tracking_number' => '']], 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        try {
            $this->connector()->arrangeShipment($auth, 'SN_8', ['packages' => [['externalPackageId' => 'PKG_8']]]);
            $this->fail('Mong đợi ShopeeApiException vì đơn UNPAID');
        } catch (ShopeeApiException $e) {
            $this->assertMatchesRegularExpression('/READY_TO_SHIP|UNPAID/', $e->getMessage());
        }
        Http::assertNotSent(fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), '/api/v2/logistics/get_shipping_parameter'));
    }

    public function test_verify_webhook_signature_falls_back_to_request_url_when_push_url_misconfigured(): void
    {
        // Root cause `shopee.webhook.signature_mismatch`: sau reverse proxy, `push_url` cấu hình có thể lệch
        // scheme/host so với URL công khai mà Shopee ký. Verifier phải thử cả URL của chính request.
        $this->connector();
        $raw = json_encode(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])], JSON_UNESCAPED_SLASHES);
        $requestUrl = 'https://app.cmbcore.com/webhook/shopee';
        // push_url cấu hình SAI (vd host nội bộ) — Shopee ký bằng URL công khai = $requestUrl.
        config(['integrations.shopee.push_url' => 'http://internal-host/webhook/shopee']);
        $sign = hash_hmac('sha256', $requestUrl.'|'.$raw, 'PARTNER_KEY');
        $req = Request::create($requestUrl, 'POST', content: $raw);
        $req->headers->set('Authorization', $sign);

        $this->assertTrue($this->connector()->verifyWebhookSignature($req));
    }

    public function test_get_shipping_document_polls_then_downloads(): void
    {
        Http::fake([
            '*/api/v2/logistics/get_shipping_document_parameter*' => Http::response(ShopeeFixtures::documentParameter('THERMAL_AIR_WAYBILL'), 200),
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFixtures::createDocument(), 200),
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFixtures::documentResult('READY'), 200),
            '*/api/v2/logistics/download_shipping_document*' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $doc = $this->connector()->getShippingDocument($auth, 'SN_1', ['externalPackageId' => 'PKG_1', 'tracking_no' => 'SPXVN123']);
        $this->assertSame('application/pdf', $doc['mime']);
        $this->assertStringContainsString('%PDF', $doc['bytes']);
        $this->assertStringEndsWith('.pdf', $doc['filename']);
        // create_shipping_document PHẢI gửi tracking_number (thiếu ⇒ logistics.tracking_number_invalid: Shopee 0 tem)
        // + loại tem gợi ý từ get_shipping_document_parameter (THERMAL cho SPX).
        Http::assertSent(function (\Illuminate\Http\Client\Request $r) {
            if (! str_contains($r->url(), '/api/v2/logistics/create_shipping_document')) {
                return false;
            }
            $entry = ($r->data()['order_list'][0] ?? []);

            return ($entry['tracking_number'] ?? null) === 'SPXVN123'
                && ($entry['shipping_document_type'] ?? null) === 'THERMAL_AIR_WAYBILL';
        });
    }

    public function test_get_shipping_document_surfaces_batch_fail_reason(): void
    {
        // Shopee create_shipping_document trả `common.batch_api_all_failed` ở envelope, lý do THẬT nằm trong
        // result_list[].fail_error/fail_message. Trước đây connector vứt bỏ detail ⇒ log vô dụng. Phải bóc ra.
        Http::fake([
            '*/api/v2/logistics/create_shipping_document*' => Http::response([
                'error' => 'common.batch_api_all_failed',
                'message' => 'All failed, please check result_list for detail',
                'response' => ['result_list' => [[
                    'order_sn' => 'SN_1', 'package_number' => 'PKG_1',
                    'fail_error' => 'logistics.package_number_not_exist',
                    'fail_message' => 'Package is not ready for document creation',
                ]]],
            ], 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        try {
            $this->connector()->getShippingDocument($auth, 'SN_1', ['externalPackageId' => 'PKG_1']);
            $this->fail('Expected ShopeeApiException');
        } catch (ShopeeApiException $e) {
            $this->assertStringContainsString('Package is not ready for document creation', $e->getMessage());
            $this->assertStringContainsString('logistics.package_number_not_exist', $e->getMessage());
        }
    }

    public function test_get_shipping_document_failed_throws(): void
    {
        config(['integrations.shopee.document_poll_attempts' => 2, 'integrations.shopee.document_poll_sleep_ms' => 0]);
        Http::fake([
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFixtures::createDocument(), 200),
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFixtures::documentResult('FAILED'), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->expectException(ShopeeApiException::class);
        $this->connector()->getShippingDocument($auth, 'SN_1', ['externalPackageId' => 'PKG_1']);
    }

    public function test_push_ready_to_ship_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
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
        $this->assertSame('222', $l->externalSkuId);
        $this->assertSame('SKU-A-RED', $l->sellerSku);
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

    public function test_listing_external_sku_id_round_trips_into_update_stock(): void
    {
        Http::fake([
            '*/api/v2/product/get_item_list*' => Http::response(ShopeeFixtures::itemList(), 200),
            '*/api/v2/product/get_item_base_info*' => Http::response(ShopeeFixtures::itemBaseInfo(), 200),
            '*/api/v2/product/get_model_list*' => Http::response(ShopeeFixtures::modelList(), 200),
            '*/api/v2/product/update_stock*' => Http::response(['error' => '', 'response' => []], 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');
        $listing = $this->connector()->fetchListings($auth, ['pageSize' => 50])->items[0];

        $this->connector()->updateStock($auth, $listing->externalSkuId, 9, ['external_product_id' => $listing->externalProductId]);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/product/update_stock')
            && $r['stock_list'][0]['model_id'] === 222
            && $r['stock_list'][0]['seller_stock'][0]['stock'] === 9);
    }

    public function test_fetch_settlements_maps_escrow_to_settlement_dto(): void
    {
        Http::fake([
            '*/api/v2/payment/get_escrow_list*' => Http::response(ShopeeFixtures::escrowList(), 200),
            '*/api/v2/payment/get_escrow_detail*' => Http::response(ShopeeFixtures::escrowDetail(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');
        // Get connector first (calls configure() which sets finance_enabled=false), then override.
        $connector = $this->connector();
        config(['integrations.shopee.finance_enabled' => true]);

        $page = $connector->fetchSettlements($auth, [
            'from' => CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'to' => CarbonImmutable::parse('2026-01-15T00:00:00Z'),
        ]);
        $this->assertCount(1, $page->items);
        $s = $page->items[0];
        $this->assertSame(210000, $s->totalPayout);
        $this->assertNotEmpty($s->lines);
        $types = array_map(fn ($l) => $l->feeType, $s->lines);
        $this->assertContains('commission', $types);
        $this->assertContains('revenue', $types);
    }

    public function test_fetch_settlements_paginates_escrow_list(): void
    {
        // escrow_list returns more=true on page 1, more=false on page 2.
        // escrow_detail is called for each SN found across both pages.
        $page1Response = ShopeeFixtures::escrowListPage1();
        $page2Response = ShopeeFixtures::escrowListPage2();

        Http::fake(function (\Illuminate\Http\Client\Request $r) use ($page1Response, $page2Response) {
            if (str_contains($r->url(), '/api/v2/payment/get_escrow_list')) {
                $pageNo = (int) ($r['page_no'] ?? 1);

                return Http::response($pageNo === 1 ? $page1Response : $page2Response, 200);
            }
            // escrow_detail — return a minimal valid detail for whatever SN is requested
            $sn = (string) ($r['order_sn'] ?? 'SN_UNKNOWN');

            return Http::response(['error' => '', 'response' => [
                'order_sn' => $sn,
                'order_income' => [
                    'escrow_amount' => 100000, 'buyer_total_amount' => 120000,
                    'commission_fee' => 10000, 'service_fee' => 2000, 'seller_transaction_fee' => 1000,
                    'actual_shipping_fee' => 8000, 'shopee_shipping_rebate' => 5000,
                    'voucher_from_seller' => 0, 'voucher_from_shopee' => 0,
                ],
            ]], 200);
        });

        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');
        $connector = $this->connector();
        config(['integrations.shopee.finance_enabled' => true]);

        $connector->fetchSettlements($auth, [
            'from' => CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'to' => CarbonImmutable::parse('2026-01-15T00:00:00Z'),
        ]);

        // escrow_list must have been called at least twice (once per page)
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/payment/get_escrow_list') && (int) ($r['page_no'] ?? 0) === 1);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/payment/get_escrow_list') && (int) ($r['page_no'] ?? 0) === 2);
        // escrow_detail must have been fetched for both SNs
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/payment/get_escrow_detail') && ($r['order_sn'] ?? '') === 'SN_P1');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/payment/get_escrow_detail') && ($r['order_sn'] ?? '') === 'SN_P2');
    }

    public function test_map_status_covers_full_table(): void
    {
        $c = $this->connector();
        $expected = [
            'UNPAID' => StandardOrderStatus::Unpaid,
            'READY_TO_SHIP' => StandardOrderStatus::Pending,
            'PROCESSED' => StandardOrderStatus::Processing,
            'RETRY_SHIP' => StandardOrderStatus::Processing,
            'SHIPPED' => StandardOrderStatus::Shipped,
            'TO_CONFIRM_RECEIVE' => StandardOrderStatus::Delivered,
            'COMPLETED' => StandardOrderStatus::Completed,
            'IN_CANCEL' => StandardOrderStatus::Processing,
            'CANCELLED' => StandardOrderStatus::Cancelled,
            'TO_RETURN' => StandardOrderStatus::Returning,
        ];
        foreach ($expected as $raw => $std) {
            $this->assertSame($std, $c->mapStatus($raw), "status {$raw}");
        }
    }

    public function test_arrange_shipment_uses_dropoff_when_offered(): void
    {
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameterDropoff(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
            // chưa ship ⇒ tracking rỗng (idempotency check), sau ship mới có TRK123
            // Sau ship_order, get_tracking_number trả mã (chỉ gọi 1 lần — idempotency nay dựa trên order_status,
            // KHÔNG còn dò tracking trước để short-circuit).
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $res = $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [['externalPackageId' => 'PKG_1']]]);

        $this->assertSame('TRK123', $res['tracking_no']);
        // ship_order request body must contain a 'dropoff' key
        Http::assertSent(function (\Illuminate\Http\Client\Request $r) {
            if (! str_contains($r->url(), '/api/v2/logistics/ship_order')) {
                return false;
            }
            $data = $r->data();

            return array_key_exists('dropoff', $data);
        });
    }

    public function test_arrange_shipment_omits_package_number_for_unsplit_order(): void
    {
        // Shopee trả package_number trong package_list cho cả đơn 1 kiện (CHƯA tách). Gửi package_number ở
        // ship_order cho đơn chưa tách ⇒ lỗi `logistics.ship_order_not_need_pacakge_number`. Đơn 1 kiện ⇒ KHÔNG gửi.
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
            // Sau ship_order, get_tracking_number trả mã (chỉ gọi 1 lần — idempotency nay dựa trên order_status,
            // KHÔNG còn dò tracking trước để short-circuit).
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [['externalPackageId' => 'PKG_1']]]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $r) {
            if (! str_contains($r->url(), '/api/v2/logistics/ship_order')) {
                return false;
            }

            return ! array_key_exists('package_number', $r->data());
        });
    }

    public function test_arrange_shipment_sends_package_number_for_split_order(): void
    {
        // Đơn ĐÃ tách (≥2 kiện) ⇒ ship_order PHẢI kèm package_number để chỉ định kiện cần giao.
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
            // Sau ship_order, get_tracking_number trả mã (chỉ gọi 1 lần — idempotency nay dựa trên order_status,
            // KHÔNG còn dò tracking trước để short-circuit).
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [
            ['externalPackageId' => 'PKG_1'],
            ['externalPackageId' => 'PKG_2'],
        ]]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $r) {
            if (! str_contains($r->url(), '/api/v2/logistics/ship_order')) {
                return false;
            }

            return ($r->data()['package_number'] ?? null) === 'PKG_1';
        });
    }

    public function test_fetch_returns_maps_to_return_dto(): void
    {
        config(['integrations.shopee.returns_enabled' => true]);
        Http::fake(['*/api/v2/returns/get_return_list*' => Http::response(['error' => '', 'response' => ['return' => [[
            'return_sn' => 'RSN_1', 'order_sn' => 'SN_1', 'status' => 'REQUESTED', 'reason' => 'DEFECTIVE',
            'refund_amount' => 50000, 'currency' => 'VND', 'create_time' => 1700000000, 'update_time' => 1700000100,
            'item' => [['item_sku' => 'SKU-A', 'name' => 'Áo', 'amount' => 1]],
        ]], 'more' => false]], 200)]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchReturns($auth, ['updatedFrom' => CarbonImmutable::now()->subDay()]);

        $this->assertCount(1, $page->items);
        $this->assertSame('RSN_1', $page->items[0]->externalReturnId);
        $this->assertSame('SN_1', $page->items[0]->externalOrderId);
        $this->assertSame(AfterSalesStatus::Requested, $page->items[0]->status);
        $this->assertSame(50000, $page->items[0]->refundAmount);
    }

    public function test_fetch_returns_splits_window_to_15_days(): void
    {
        // get_return_list giới hạn create_time_from..create_time_to ≤ 15 ngày. updatedFrom xa hơn 15 ngày ⇒
        // connector PHẢI chia cửa sổ (mỗi request ≤ 15 ngày) + trả cursor để tiếp tục cửa sổ kế.
        config(['integrations.shopee.returns_enabled' => true]);
        Http::fake(['*/api/v2/returns/get_return_list*' => Http::response(['error' => '', 'response' => ['return' => [], 'more' => false]], 200)]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchReturns($auth, ['updatedFrom' => CarbonImmutable::parse('2026-01-01T00:00:00Z')]);

        $this->assertTrue($page->hasMore);
        $this->assertNotNull($page->nextCursor);
        $this->assertStringContainsString(':', $page->nextCursor); // "windowStart:pageNo"
        Http::assertSent(function (\Illuminate\Http\Client\Request $r) {
            if (! str_contains($r->url(), '/api/v2/returns/get_return_list')) {
                return false;
            }
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $qs);
            $from = (int) ($qs['create_time_from'] ?? 0);
            $to = (int) ($qs['create_time_to'] ?? 0);

            return $from > 0 && $to > 0 && ($to - $from) <= 15 * 86400;
        });
    }

    public function test_decide_return_calls_confirm_endpoint(): void
    {
        config(['integrations.shopee.returns_enabled' => true]);
        Http::fake(['*/api/v2/returns/confirm*' => Http::response(['error' => '', 'response' => []], 200)]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->connector()->decideReturn($auth, 'RSN_1', 'approve');

        Http::assertSent(fn (\Illuminate\Http\Client\Request $r) => str_contains($r->url(), '/api/v2/returns/confirm'));
    }
}
