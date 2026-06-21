<?php

namespace Tests\Feature\Orders;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giữ mô hình 2 bước nội bộ cho đơn sàn: "Chuẩn bị hàng" → Processing; chỉ thao tác "Đã gói & sẵn sàng bàn
 * giao" (markPacked, đi qua OrderStatusSync::apply — KHÔNG qua doUpsert) mới đẩy ReadyToShip. Một số sàn (vd
 * Lazada vài delivery_type) tự đẩy đơn lên ready_to_ship ngay sau /order/pack ⇒ đồng bộ ngược sẽ "tự nhảy"
 * Processing→Chờ bàn giao phi lý. Guard ở OrderUpsertService chặn ĐÚNG nhịp đó. SYNC_HOLD_CHANNEL_READY_TO_SHIP.
 */
class OrderUpsertReadyToShipHoldTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    private OrderUpsertService $upsert;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop RTS hold']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ-SH', 'shop_name' => 'Lazada', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
        $this->upsert = app(OrderUpsertService::class);
    }

    private function dto(string $extId, string $rawStatus, CarbonImmutable $updatedAt): OrderDTO
    {
        return new OrderDTO(
            externalOrderId: $extId, source: 'lazada', rawStatus: $rawStatus, sourceUpdatedAt: $updatedAt,
            orderNumber: $extId, paymentStatus: 'paid', placedAt: $updatedAt->subHours(2), paidAt: $updatedAt->subHours(2),
            shippedAt: null, deliveredAt: null, completedAt: null, cancelledAt: null, cancelReason: null,
            buyer: ['name' => 'B'], shippingAddress: [], currency: 'VND', itemTotal: 100000, shippingFee: 0,
            platformDiscount: 0, sellerDiscount: 0, tax: 0, codAmount: 0, grandTotal: 100000, isCod: false,
            fulfillmentType: null, items: [], packages: [], raw: [],
        );
    }

    private function sync(string $extId, string $rawStatus, S $status, CarbonImmutable $at): Order
    {
        return $this->upsert->upsertWithStatus($this->dto($extId, $rawStatus, $at), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling', $status);
    }

    public function test_channel_ready_to_ship_does_not_auto_advance_internal_processing(): void
    {
        $this->sync('LZ1', 'packed', S::Processing, CarbonImmutable::now()->subMinutes(10));
        $after = $this->sync('LZ1', 'ready_to_ship', S::ReadyToShip, CarbonImmutable::now());

        $this->assertSame(S::Processing, $after->status, 'Sàn ready_to_ship KHÔNG được tự nhảy Processing→ReadyToShip qua sync.');
        $this->assertSame('ready_to_ship', $after->raw_status, 'raw_status thật của sàn vẫn được lưu.');
    }

    public function test_hold_can_be_disabled_via_config(): void
    {
        config(['integrations.sync.hold_channel_ready_to_ship' => false]);
        $this->sync('LZ2', 'packed', S::Processing, CarbonImmutable::now()->subMinutes(10));
        $after = $this->sync('LZ2', 'ready_to_ship', S::ReadyToShip, CarbonImmutable::now());

        $this->assertSame(S::ReadyToShip, $after->status, 'Tắt cờ ⇒ theo sát sàn (nhảy ReadyToShip).');
    }

    public function test_fresh_order_arriving_ready_to_ship_is_not_held(): void
    {
        // Đơn chưa từng "Chuẩn bị hàng" trong app (created) mà sàn đã ở ready_to_ship ⇒ vẫn set ReadyToShip.
        $o = $this->sync('LZ3', 'ready_to_ship', S::ReadyToShip, CarbonImmutable::now());
        $this->assertSame(S::ReadyToShip, $o->status);
    }

    public function test_real_forward_to_shipped_is_not_blocked(): void
    {
        $this->sync('LZ4', 'packed', S::Processing, CarbonImmutable::now()->subMinutes(10));
        $after = $this->sync('LZ4', 'shipped', S::Shipped, CarbonImmutable::now());
        $this->assertSame(S::Shipped, $after->status, 'Tiến thật lên Shipped KHÔNG bị guard chặn.');
    }

    public function test_prepared_order_not_regressed_to_pending_by_sync(): void
    {
        // Sticky-forward (fix gốc "nhảy lung tung"): đơn đã "Chuẩn bị hàng" (Processing) ⇒ sync KHÔNG kéo lùi
        // về Pending dù sàn chưa kịp cập nhật (vd Shopee READY_TO_SHIP→pending trong lúc chờ chuyển PROCESSED).
        $this->sync('LZ5', 'packed', S::Processing, CarbonImmutable::now()->subMinutes(10));
        $after = $this->sync('LZ5', 'ready_to_ship', S::Pending, CarbonImmutable::now());

        $this->assertSame(S::Processing, $after->status, 'Đơn đã chuẩn bị KHÔNG được kéo lùi về Chờ xử lý.');
        $this->assertSame('ready_to_ship', $after->raw_status, 'Vẫn lưu raw_status thật của sàn.');
    }

    public function test_ready_to_ship_order_not_regressed_to_pending_by_sync(): void
    {
        // Đơn đã ở Chờ bàn giao (do markPacked nội bộ — set thẳng để mô phỏng) cũng không bị sync kéo lùi.
        $order = $this->sync('LZ6', 'packed', S::Processing, CarbonImmutable::now()->subMinutes(20));
        $order->forceFill(['status' => S::ReadyToShip])->save();   // mô phỏng markPacked (đi qua OrderStatusSync, không qua doUpsert)

        $after = $this->sync('LZ6', 'pending', S::Pending, CarbonImmutable::now());

        $this->assertSame(S::ReadyToShip, $after->status, 'Đơn Chờ bàn giao KHÔNG bị kéo lùi về Chờ xử lý.');
    }

    public function test_terminal_cancelled_not_revived_by_lower_status(): void
    {
        // Đơn đã HUỶ (terminal) ⇒ update trễ map về processing (vd Shopee IN_CANCEL) KHÔNG được hồi sinh.
        $this->sync('LZ7', 'canceled', S::Cancelled, CarbonImmutable::now()->subMinutes(10));
        $after = $this->sync('LZ7', 'in_cancel', S::Processing, CarbonImmutable::now());

        $this->assertSame(S::Cancelled, $after->status, 'Đơn đã huỷ KHÔNG bị kéo về Đang xử lý.');
        $this->assertFalse((bool) $after->has_issue, 'Lùi-được-giữ KHÔNG gắn cờ has_issue (hết cảnh báo nhiễu).');
    }

    public function test_webhook_does_not_revive_cancelled_order(): void
    {
        // Ca thật: Shopee gửi CANCELLED rồi 3s sau gửi IN_CANCEL (→processing) qua webhook.
        $this->sync('LZ8', 'canceled', S::Cancelled, CarbonImmutable::now()->subMinutes(10));
        $after = $this->upsert->applyStatusFromWebhook(
            (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'lazada', 'LZ8', S::Processing, 'IN_CANCEL',
        );

        $this->assertSame(S::Cancelled, $after->status, 'Webhook trễ KHÔNG hồi sinh đơn đã huỷ.');
        $this->assertSame('IN_CANCEL', $after->raw_status, 'Vẫn lưu raw_status thật của sàn.');
    }

    public function test_shipped_not_regressed_to_pre_shipment(): void
    {
        $this->sync('LZ9', 'shipped', S::Shipped, CarbonImmutable::now()->subMinutes(10));
        $after = $this->sync('LZ9', 'ready_to_ship', S::ReadyToShip, CarbonImmutable::now());

        $this->assertSame(S::Shipped, $after->status, 'Đơn đã giao cho ĐVVC KHÔNG quay về trước-giao-hàng.');
    }
}
