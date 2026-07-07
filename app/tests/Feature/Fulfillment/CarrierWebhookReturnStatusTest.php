<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 9 — GHN webhook `returning`/`return*` ⇒ order Returning ("đang hoàn"); `returned` (đã về
 * kho) ⇒ order ReturnedRefunded ("đã trả/hoàn", terminal).
 *
 * Shipment/order xây trực tiếp qua model (không đi qua /orders/{id}/ship — pattern giống
 * MarkPackedCancelledOrderTest) để tách khỏi luồng tạo đơn GHN thật (validateShipmentPayload,
 * lỗi baseline sẵn có trên main — không liên quan Task 9, xem test-verify-baseline).
 */
class CarrierWebhookReturnStatusTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private CarrierAccount $ghnAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'GhnShop return-status']);
        $this->ghnAccount = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'carrier' => 'ghn',
            'name' => 'GHN — Kho HN',
            'credentials' => ['token' => 'TEST-TOKEN-123', 'shop_id' => 9999],
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /** @return array{0:Order,1:Shipment} */
    private function makeGhnShipment(string $tracking, string $shipmentStatus = Shipment::STATUS_IN_TRANSIT): array
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual',
            'channel_account_id' => null,
            'external_order_id' => null,
            'order_number' => 'MAN-'.$tracking,
            'status' => StandardOrderStatus::Shipped,
            'raw_status' => 'shipped',
            'shipping_address' => ['fullName' => 'Trần B', 'phone' => '0912345678'],
            'currency' => 'VND',
            'grand_total' => 300000,
            'item_total' => 300000,
            'is_cod' => false,
            'placed_at' => now()->subDay(),
            'source_updated_at' => now()->subDay(),
            'has_issue' => false,
            'tags' => [],
            'packages' => [],
        ]);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'carrier' => 'ghn',
            'carrier_account_id' => $this->ghnAccount->getKey(),
            'tracking_no' => $tracking,
            'status' => $shipmentStatus,
        ]);

        return [$order, $shipment];
    }

    public function test_returned_moves_order_to_returned_refunded(): void
    {
        [$order] = $this->makeGhnShipment('GH-RET-1');

        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GH-RET-1', 'Status' => 'returned', 'Time' => '2026-05-16T10:00:00+07:00',
        ], ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'GH-RET-1')->first();
        $this->assertSame(Shipment::STATUS_RETURNED, $sh->status);

        $this->assertSame(StandardOrderStatus::ReturnedRefunded->value, $order->refresh()->status->value);
    }

    public function test_returning_moves_order_to_returning(): void
    {
        [$order] = $this->makeGhnShipment('GH-RET-2');

        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GH-RET-2', 'Status' => 'returning', 'Time' => '2026-05-16T10:00:00+07:00',
        ], ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'GH-RET-2')->first();
        $this->assertSame(Shipment::STATUS_RETURNING, $sh->status);

        $this->assertSame(StandardOrderStatus::Returning->value, $order->refresh()->status->value);
    }

    /**
     * OPEN_STATUSES fix: shipment phải vẫn `open()` sau khi rơi vào `returning` để webhook `returned`
     * kế tiếp còn tìm thấy nó (findShipment dùng ->open()) và đẩy tiếp order sang ReturnedRefunded.
     */
    public function test_returning_then_returned_sequence_reaches_returned_refunded(): void
    {
        [$order, $shipment] = $this->makeGhnShipment('GH-RET-3');

        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GH-RET-3', 'Status' => 'returning', 'Time' => '2026-05-16T10:00:00+07:00',
        ], ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $this->assertSame(StandardOrderStatus::Returning->value, $order->refresh()->status->value);

        $resp = $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GH-RET-3', 'Status' => 'returned', 'Time' => '2026-05-16T12:00:00+07:00',
        ], ['Token' => 'TEST-TOKEN-123']);
        // Nếu OPEN_STATUSES thiếu STATUS_RETURNING, findShipment() không còn tìm thấy shipment (đã
        // rơi khỏi scope open()) ⇒ controller vẫn ack 200 nhưng `shipment_id` sẽ null (not-found path).
        $resp->assertOk()->assertJsonPath('data.acknowledged', true)->assertJsonPath('data.shipment_id', $shipment->getKey());

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'GH-RET-3')->first();
        $this->assertSame(Shipment::STATUS_RETURNED, $sh->status);

        $this->assertSame(StandardOrderStatus::ReturnedRefunded->value, $order->refresh()->status->value);
    }
}
