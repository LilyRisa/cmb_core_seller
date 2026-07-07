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
 * Task 10 — GHN webhook `CODFailedFee` (thu tiền hàng khi giao thất bại) ⇒ ghi vào
 * shipment.failed_collect_collected. 0 (khách từ chối) vẫn phải ghi (phân biệt "chưa có dữ liệu" = null).
 *
 * Pattern giống CarrierWebhookReturnStatusTest (Task 9): dựng order/shipment trực tiếp qua model,
 * bỏ qua luồng tạo đơn GHN thật (lỗi baseline sẵn có, không liên quan task này).
 */
class CarrierWebhookReturnOutcomeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private CarrierAccount $ghnAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'GhnShop return-outcome']);
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
    private function makeGhnShipment(string $tracking): array
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
            'is_cod' => true,
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
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);

        return [$order, $shipment];
    }

    public function test_failed_delivery_with_collected_fee_is_recorded(): void
    {
        [, $shipment] = $this->makeGhnShipment('GHN200');

        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GHN200', 'Status' => 'delivery_fail', 'CODFailedFee' => 30000,
        ], ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $this->assertSame(30000, (int) $shipment->refresh()->failed_collect_collected);
    }

    public function test_failed_delivery_refused_records_zero(): void
    {
        [, $shipment] = $this->makeGhnShipment('GHN201');

        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GHN201', 'Status' => 'delivery_fail', 'CODFailedFee' => 0,
        ], ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $this->assertSame(0, (int) $shipment->refresh()->failed_collect_collected);
    }

    /** Webhook không kèm CODFailedFee ⇒ giữ nguyên null (không ghi đè bằng 0 giả). */
    public function test_webhook_without_fee_field_leaves_column_null(): void
    {
        [, $shipment] = $this->makeGhnShipment('GHN202');

        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GHN202', 'Status' => 'delivery_fail',
        ], ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $this->assertNull($shipment->refresh()->failed_collect_collected);
    }

    /** cod_collected (CODAmount) ghi khi trả hàng thành công kèm COD đã thu. */
    public function test_delivered_with_cod_amount_records_cod_collected(): void
    {
        [, $shipment] = $this->makeGhnShipment('GHN203');

        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GHN203', 'Status' => 'delivered', 'CODAmount' => 300000,
        ], ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $this->assertSame(300000, (int) $shipment->refresh()->cod_collected);
    }
}
