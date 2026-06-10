<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Jobs\BackfillChannelTracking;
use CMBcoreSeller\Modules\Fulfillment\Jobs\FetchChannelLabel;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures as ShopeeFx;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/**
 * Fulfillment guards & label/tracking reliability for MARKETPLACE orders (channel_account_id != null):
 *  - A : block "Chuẩn bị hàng" for ON_HOLD orders (TikTok cấm fulfill + chưa có địa chỉ).
 *  - B1: async-retry tem cho mọi sàn (không chỉ Lazada).
 *  - B2: chặn bàn giao khi đơn sàn chưa có tem thật.
 */
class ChannelOrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class, FetchChannelLabel::class, BackfillChannelTracking::class]);
        F::configure();
        // phpunit.xml tắt INTEGRATIONS_TIKTOK_FULFILLMENT ⇒ bật lại cho nhóm test "luồng A".
        config(['integrations.tiktok.fulfillment_enabled' => true]);
        $this->tenant = Tenant::create(['name' => 'Shop channel ff']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => F::SHOP_ID,
            'shop_name' => 'Cửa hàng test', 'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tk', 'refresh_token' => 'rk', 'token_expires_at' => now()->addDays(7),
            'meta' => ['shop_cipher' => F::SHOP_CIPHER],
        ]);
    }

    private function channelOrder(array $overrides = []): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok',
            'channel_account_id' => $this->account->getKey(),
            'external_order_id' => 'TT-'.uniqid(), 'order_number' => 'TT-1',
            'status' => StandardOrderStatus::Pending, 'raw_status' => 'AWAITING_SHIPMENT',
            'shipping_address' => ['fullName' => 'A', 'phone' => '0900000000'], 'currency' => 'VND',
            'grand_total' => 100000, 'item_total' => 100000, 'is_cod' => false,
            'placed_at' => now()->subHour(), 'source_updated_at' => now()->subHour(),
            'has_issue' => false, 'tags' => [], 'packages' => [['externalPackageId' => 'PKG1']],
        ], $overrides));
    }

    /** Fake luồng arrange TikTok: ship OK + package detail trả tracking; tem fail (để test retry/issue). */
    private function fakeTikTokArrange(): void
    {
        Http::fake([
            '*/fulfillment/202309/packages/*/ship*' => Http::response(F::envelope([])),
            '*/fulfillment/202309/packages/*/shipping_documents*' => Http::response(['code' => 5000, 'message' => 'not ready', 'data' => []]),
            '*/fulfillment/202309/packages/*' => Http::response(F::envelope(['tracking_number' => 'TT-TRACK', 'shipping_provider_name' => 'GHN'])),
        ]);
    }

    // --- A: ON_HOLD không cho "Chuẩn bị hàng" -------------------------------------

    public function test_prepare_blocked_for_on_hold_order(): void
    {
        $this->fakeTikTokArrange();   // dù arrange "thành công", guard phải chặn TRƯỚC khi gọi sàn
        $order = $this->channelOrder(['raw_status' => 'ON_HOLD']);

        $message = null;
        try {
            app(ShipmentService::class)->createForOrder($order, null, null);
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
        }

        $this->assertNotNull($message, 'createForOrder phải ném lỗi cho đơn ON_HOLD');
        $this->assertStringContainsStringIgnoringCase('ON_HOLD', $message);
        $this->assertSame(0, Shipment::withoutGlobalScope(TenantScope::class)->where('order_id', $order->getKey())->count());
        Http::assertNothingSent();   // chặn trước khi gọi sàn
    }

    public function test_prepare_allowed_for_awaiting_shipment_order(): void
    {
        // AWAITING_SHIPMENT KHÔNG bị chặn — arrange chạy (ship ok, có tracking).
        $this->fakeTikTokArrange();
        $order = $this->channelOrder(['raw_status' => 'AWAITING_SHIPMENT']);

        $shipment = app(ShipmentService::class)->createForOrder($order, null, null);

        $this->assertSame('TT-TRACK', $shipment->tracking_no);
    }

    // --- B1: async-retry tem cho mọi sàn -----------------------------------------

    public function test_label_fetch_failure_queues_async_retry_for_tiktok(): void
    {
        $this->fakeTikTokArrange();
        $order = $this->channelOrder();

        $shipment = app(ShipmentService::class)->createForOrder($order, null, null);

        // Có tracking nhưng tem fail ⇒ phải enqueue FetchChannelLabel (trước đây chỉ Lazada mới enqueue).
        $this->assertSame('TT-TRACK', $shipment->tracking_no);
        $this->assertTrue(blank($shipment->fresh()->label_path));
        Bus::assertDispatched(FetchChannelLabel::class, fn (FetchChannelLabel $j) => $j->shipmentId === (int) $shipment->getKey());
    }

    public function test_prepare_blocked_for_shopee_unpaid_order(): void
    {
        // Shopee UNPAID ⇒ chặn "Chuẩn bị hàng" với thông báo VN, KHÔNG gọi API sàn (trước đây gọi
        // get_shipping_parameter rồi lỗi error_param khó hiểu → "vớ vẩn").
        config(['integrations.shopee.unfulfillable_raw_statuses' => ['UNPAID', 'IN_CANCEL']]);
        $shopee = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee', 'external_shop_id' => 'SP1',
            'shop_name' => 'Shopee', 'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tk', 'refresh_token' => 'rk', 'token_expires_at' => now()->addDays(7),
        ]);
        Http::fake();
        $order = $this->channelOrder(['source' => 'shopee', 'channel_account_id' => $shopee->getKey(), 'raw_status' => 'UNPAID', 'status' => StandardOrderStatus::Unpaid]);

        $message = null;
        try {
            app(ShipmentService::class)->createForOrder($order, null, null);
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
        }
        $this->assertNotNull($message, 'Phải chặn đơn Shopee UNPAID');
        $this->assertStringContainsString('UNPAID', $message);
        $this->assertSame(0, Shipment::withoutGlobalScope(TenantScope::class)->where('order_id', $order->getKey())->count());
        Http::assertNothingSent();
    }

    // --- B1b: tracking pre-assigned (đã sync) KHÔNG được skip arrange ----------------

    public function test_channel_order_with_preassigned_tracking_still_calls_arrange(): void
    {
        // Root cause Shopee `create_shipping_document → tracking_number_invalid`: Shopee pre-assign tracking ngay
        // ở READY_TO_SHIP (sync vào order.packages[].trackingNo). KHÔNG được coi là "đã arrange" → prepareChannelOrder
        // VẪN phải gọi sàn arrange (connector idempotent), dùng tracking từ arrange THẬT. (Mô phỏng bằng TikTok
        // harness; cùng cơ chế cho Shopee.) Trước fix: pre-skip ⇒ shipment giữ tracking pre-assigned, không arrange.
        $this->fakeTikTokArrange();
        $order = $this->channelOrder(['packages' => [['externalPackageId' => 'PKG1', 'trackingNo' => 'PRE-ASSIGNED']]]);

        $shipment = app(ShipmentService::class)->createForOrder($order, null, null);

        // Arrange ĐÃ chạy ⇒ dùng tracking thật của sàn, KHÔNG giữ 'PRE-ASSIGNED' đã sync.
        $this->assertSame('TT-TRACK', $shipment->tracking_no);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/fulfillment/202309/packages/'));
    }

    // --- B2: chặn bàn giao khi đơn sàn chưa có tem --------------------------------

    public function test_handover_blocked_for_channel_order_without_label(): void
    {
        $order = $this->channelOrder();
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(), 'carrier' => 'GHN',
            'tracking_no' => 'TT-TRACK', 'package_no' => 'PKG1', 'status' => Shipment::STATUS_PACKED, 'label_path' => null,
        ]);

        $this->expectException(RuntimeException::class);
        app(ShipmentService::class)->handover($shipment);
    }

    // --- B4: backfill tracking TikTok khi cấp trễ --------------------------------

    public function test_prepare_queues_tracking_backfill_when_tracking_missing(): void
    {
        // ship OK nhưng package detail CHƯA có tracking (TikTok cấp async) ⇒ enqueue job kéo lại.
        Http::fake([
            '*/fulfillment/202309/packages/*/ship*' => Http::response(F::envelope([])),
            '*/fulfillment/202309/packages/*/shipping_documents*' => Http::response(['code' => 5000, 'message' => 'x', 'data' => []]),
            '*/fulfillment/202309/packages/*' => Http::response(F::envelope([])),   // không có tracking_number
        ]);
        $order = $this->channelOrder();

        $shipment = app(ShipmentService::class)->createForOrder($order, null, null);

        $this->assertTrue(blank($shipment->tracking_no));
        Bus::assertDispatched(BackfillChannelTracking::class, fn (BackfillChannelTracking $j) => $j->shipmentId === (int) $shipment->getKey());
    }

    public function test_backfill_tracking_updates_shipment_when_available(): void
    {
        // arrange idempotent giờ thấy tracking ⇒ cập nhật shipment + clear cờ has_issue.
        Http::fake([
            '*/fulfillment/202309/packages/*/shipping_documents*' => Http::response(['code' => 5000, 'message' => 'x', 'data' => []]),
            '*/fulfillment/202309/packages/*' => Http::response(F::envelope(['tracking_number' => 'TT-LATE', 'shipping_provider_name' => 'GHN'])),
        ]);
        $order = $this->channelOrder(['has_issue' => true, 'issue_reason' => 'Đang chờ sàn cấp mã vận đơn']);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(), 'carrier' => 'GHN',
            'package_no' => 'PKG1', 'tracking_no' => null, 'status' => Shipment::STATUS_CREATED,
        ]);

        $ok = app(ShipmentService::class)->backfillChannelTracking($order, $shipment);

        $this->assertTrue($ok);
        $this->assertSame('TT-LATE', $shipment->fresh()->tracking_no);
        $this->assertFalse((bool) $order->fresh()->has_issue);
    }

    // --- B4b: Shopee lấy tem TRƯỚC khi có tracking (capability shipping.document_before_tracking) --------

    public function test_shopee_fetches_label_before_tracking_when_arranged(): void
    {
        // Shopee cấp mã vận đơn ASYNC (3PL) nhưng AWB là bước create_shipping_document ĐỘC LẬP sau arrange.
        // Đơn đã arrange dù CHƯA có tracking vẫn phải thử kéo tem ngay (không kẹt "đang xử lý" thiếu tem) +
        // vẫn enqueue backfill mã vận đơn. TikTok/Lazada KHÔNG khai cờ ⇒ giữ luồng cũ (test ngay trên).
        app(ChannelRegistry::class)->register('shopee', ShopeeConnector::class);
        ShopeeFx::configure();
        config(['integrations.shopee.document_poll_attempts' => 1, 'integrations.shopee.document_poll_sleep_ms' => 0]);
        $shopee = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee', 'external_shop_id' => '55',
            'shop_name' => 'SP', 'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'ACCESS_1', 'refresh_token' => 'rk', 'token_expires_at' => now()->addDays(7),
        ]);
        $order = $this->channelOrder([
            'source' => 'shopee', 'channel_account_id' => $shopee->getKey(),
            'raw_status' => 'READY_TO_SHIP', 'external_order_id' => 'SN_1',
            'packages' => [['externalPackageId' => 'PKG_1']],
        ]);
        Http::fake([
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFx::orderDetail(), 200),
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFx::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFx::shipOrder(), 200),
            // Sàn CHƯA cấp mã vận đơn (3PL trễ).
            '*/api/v2/logistics/get_tracking_number*' => Http::response(['error' => '', 'response' => ['tracking_number' => '']], 200),
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFx::createDocument(), 200),
            // Tem chưa render ⇒ getShippingDocument timeout ⇒ enqueue async retry (không cần media store ở test).
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFx::documentResult('PROCESSING'), 200),
        ]);

        $shipment = app(ShipmentService::class)->createForOrder($order, null, null);

        $this->assertTrue(blank($shipment->tracking_no), 'Sàn chưa cấp mã vận đơn ⇒ tracking rỗng.');
        // ĐIỂM CỐT LÕI: đã thử kéo tem dù CHƯA có tracking (khác TikTok/Lazada — chỉ kéo sau khi có tracking).
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/api/v2/logistics/create_shipping_document'));
        // Vẫn enqueue backfill để cập nhật mã vận đơn khi sàn cấp.
        Bus::assertDispatched(BackfillChannelTracking::class, fn (BackfillChannelTracking $j) => $j->shipmentId === (int) $shipment->getKey());
    }

    // --- B5: đơn sàn loại không có tem (Lazada DBS/SOF) — terminal, dừng retry, báo đúng lý do ---------

    public function test_terminal_no_label_lazada_sof_marks_issue_and_blocks_handover_with_clear_reason(): void
    {
        // Lazada DBS/SOF: `/order/document/get` trả 50008 ⇒ connector ném ShippingDocumentUnavailable(terminal)
        // ⇒ ShipmentService đánh dấu raw.label_unavailable + has_issue, KHÔNG retry. Bàn giao báo đúng lý do
        // (KHÔNG bảo "Nhận phiếu giao hàng" vì sàn không bao giờ cấp tem cho loại đơn này).
        app(ChannelRegistry::class)
            ->register('lazada', LazadaConnector::class);
        config(['integrations.lazada.app_key' => 'k', 'integrations.lazada.app_secret' => 's', 'integrations.lazada.fulfillment_enabled' => true]);
        $lazada = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada', 'external_shop_id' => 'LZDSHOP',
            'shop_name' => 'Lz', 'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tk', 'refresh_token' => 'rk', 'token_expires_at' => now()->addDays(7),
        ]);
        $order = $this->channelOrder(['source' => 'lazada', 'channel_account_id' => $lazada->getKey(), 'raw_status' => 'ready_to_ship']);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(), 'carrier' => 'GHN',
            'tracking_no' => 'LZD-TRACK', 'package_no' => 'PKG1', 'status' => Shipment::STATUS_PACKED,
            'label_path' => null, 'raw' => ['external_item_ids' => [525769205080318]],
        ]);
        Http::fake([
            '*/order/document/get*' => Http::response(['code' => '50008', 'type' => 'ISP', 'message' => 'not support operation for  sof order', 'request_id' => 'rq', 'data' => []]),
        ]);

        app(ShipmentService::class)->retryChannelLabelFetch($order, $shipment);

        $shipment->refresh();
        $this->assertSame('lazada_dbs_sof', data_get($shipment->raw, 'label_unavailable.reason_code'));
        $this->assertTrue((bool) $order->fresh()->has_issue);
        Bus::assertNotDispatched(FetchChannelLabel::class);

        try {
            app(ShipmentService::class)->handover($shipment);
            $this->fail('Bàn giao phải bị chặn cho đơn DBS/SOF');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('DBS/SOF', $e->getMessage());
            $this->assertStringNotContainsString('Nhận phiếu giao hàng', $e->getMessage());
        }
    }

    public function test_handover_allowed_for_channel_order_with_label(): void
    {
        $order = $this->channelOrder();
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(), 'carrier' => 'GHN',
            'tracking_no' => 'TT-TRACK', 'package_no' => 'PKG1', 'status' => Shipment::STATUS_PACKED,
            'label_path' => 'tenants/'.$this->tenant->getKey().'/labels/x.pdf', 'label_url' => 'http://x/x.pdf',
        ]);

        $this->assertTrue(app(ShipmentService::class)->handover($shipment));
        $this->assertSame(Shipment::STATUS_PICKED_UP, $shipment->fresh()->status);
    }
}
