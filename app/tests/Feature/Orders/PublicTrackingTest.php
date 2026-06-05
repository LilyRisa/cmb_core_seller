<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Models\ShipmentEvent;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0030 — public order tracking by code. No auth, no tenant header. The
 * payload must mask PII and never expose costs other than the COD due.
 */
class PublicTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::query()->forceCreate(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual',
            'order_number' => 'M260605-ABCDE',
            'status' => 'shipped',
            'carrier' => 'manual',
            'buyer_name' => 'Nguyễn Văn An',
            'buyer_phone' => '0912345678',
            'shipping_address' => [
                'name' => 'Nguyễn Văn An',
                'phone' => '0912345678',
                'address' => '123 Đường Bí Mật',
                'ward' => 'Phường Test',
                'district' => 'Quận Test',
                'province' => 'Hà Nội',
            ],
            'currency' => 'VND',
            'cod_amount' => 150000,
            'grand_total' => 150000,
            'is_cod' => true,
            'placed_at' => now()->subDays(2),
        ], $overrides));
    }

    public function test_manual_order_with_carrier_shipment_returns_masked_journey(): void
    {
        $order = $this->makeOrder();
        OrderItem::forceCreate([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'external_item_id' => 'i1', 'name' => 'Áo thun', 'variation' => 'Đỏ / L',
            'quantity' => 2, 'unit_price' => 75000, 'subtotal' => 150000,
        ]);
        $shipment = Shipment::forceCreate([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'carrier' => 'ghn', 'tracking_no' => 'GHN999', 'status' => Shipment::STATUS_IN_TRANSIT,
            'cod_amount' => 150000, 'fee' => 20000,
        ]);
        ShipmentEvent::forceCreate([
            'tenant_id' => $this->tenant->getKey(), 'shipment_id' => $shipment->getKey(),
            'code' => 'picked', 'description' => 'Đã lấy hàng', 'status' => 'picked_up',
            'occurred_at' => now()->subDay(), 'source' => 'carrier',
        ]);

        $res = $this->getJson('/api/v1/public/track?code=M260605-ABCDE')->assertOk();

        $res->assertJsonPath('data.order_number', 'M260605-ABCDE')
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.status_label', 'Đang vận chuyển')
            ->assertJsonPath('data.carrier_name', 'GHN')
            ->assertJsonPath('data.cod.amount', 150000)
            ->assertJsonPath('data.cod.is_cod', true)
            ->assertJsonPath('data.recipient.name', 'Nguyễn Văn ***')
            ->assertJsonPath('data.recipient.phone', '091*****78')
            ->assertJsonPath('data.recipient.area', 'Phường Test, Quận Test, Hà Nội')
            ->assertJsonPath('data.timeline.0.label', 'Đã lấy hàng')
            ->assertJsonPath('data.items.0.name', 'Áo thun')
            ->assertJsonPath('data.items.0.qty', 2);

        // Steps: processing done, shipped active.
        $this->assertSame('done', $res->json('data.steps.0.state'));
        $this->assertSame('process', $res->json('data.steps.1.state'));

        // No leak of the detailed street address, the full phone, or any cost field.
        $raw = $res->getContent();
        $this->assertStringNotContainsString('Bí Mật', $raw);
        $this->assertStringNotContainsString('0912345678', $raw);
        $this->assertStringNotContainsString('grand_total', $raw);
        $this->assertStringNotContainsString('unit_price', $raw);
        $this->assertStringNotContainsString('shipping_fee', $raw);
    }

    public function test_self_shipping_manual_order_falls_back_to_status_history(): void
    {
        $order = $this->makeOrder(['order_number' => 'M260605-SELF1']);
        OrderStatusHistory::forceCreate([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'from_status' => 'processing', 'to_status' => 'shipped', 'source' => 'user',
            'changed_at' => now()->subDay(),
        ]);

        $res = $this->getJson('/api/v1/public/track?code=M260605-SELF1')->assertOk();

        $res->assertJsonPath('data.carrier_name', null)
            ->assertJsonPath('data.timeline.0.label', 'Đang vận chuyển');
    }

    public function test_non_manual_order_is_not_exposed(): void
    {
        $this->makeOrder(['order_number' => 'TT-1', 'source' => 'tiktok']);

        $this->getJson('/api/v1/public/track?code=TT-1')->assertNotFound();
    }

    public function test_unknown_or_empty_code_returns_404(): void
    {
        $this->getJson('/api/v1/public/track?code=DOES-NOT-EXIST')->assertNotFound();
        $this->getJson('/api/v1/public/track')->assertNotFound();
    }
}
