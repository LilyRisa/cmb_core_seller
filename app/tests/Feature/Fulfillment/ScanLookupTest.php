<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** POST /scan-lookup — resolve mã quét → chi tiết đơn (kiểm tra / xác nhận hoàn). Read-only. */
class ScanLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop scan lookup']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual',
            'channel_account_id' => null,
            'external_order_id' => null,
            'order_number' => 'SL-'.uniqid(),
            'status' => StandardOrderStatus::Processing,
            'raw_status' => 'processing',
            'shipping_address' => ['fullName' => 'Test', 'phone' => '0900000000'],
            'currency' => 'VND',
            'grand_total' => 100000,
            'item_total' => 100000,
            'is_cod' => false,
            'placed_at' => now()->subHour(),
            'source_updated_at' => now()->subHour(),
            'has_issue' => false,
            'issue_reason' => null,
            'tags' => [],
            'packages' => [],
        ], $overrides));
    }

    private function makeItem(Order $order): OrderItem
    {
        return OrderItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'external_item_id' => 'EXT-ITEM-1',
            'external_product_id' => 'EXT-PROD-1',
            'external_sku_id' => 'EXT-SKU-1',
            'seller_sku' => 'SKU-XYZ',
            'name' => 'Áo thun',
            'quantity' => 2,
            'unit_price' => 50000,
            'subtotal' => 100000,
        ]);
    }

    private function makeShipment(Order $order, array $overrides = []): Shipment
    {
        return Shipment::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'carrier' => 'manual',
            'tracking_no' => 'SL-TN-'.uniqid(),
            'status' => Shipment::STATUS_CREATED,
            'label_path' => 'labels/test-label.pdf',
        ], $overrides));
    }

    public function test_resolves_by_tracking_no_with_items_and_shipment(): void
    {
        $order = $this->makeOrder();
        $this->makeItem($order);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-lookup', ['code' => $shipment->tracking_no]);

        $resp->assertOk()
            ->assertJsonPath('data.id', $order->getKey())
            ->assertJsonPath('data.order_number', $order->order_number)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.shipment.tracking_no', $shipment->tracking_no)
            ->assertJsonPath('data.items.0.seller_sku', 'SKU-XYZ')
            ->assertJsonPath('data.items.0.unit_price', 50000);
    }

    public function test_resolves_by_order_number(): void
    {
        $order = $this->makeOrder(['order_number' => 'ORDER-9001']);
        $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-lookup', ['code' => 'ORDER-9001']);

        $resp->assertOk()->assertJsonPath('data.id', $order->getKey());
    }

    /** Đơn hoàn: shipment ở trạng thái RETURNED (đã đóng) — vẫn phải resolve được. */
    public function test_resolves_returned_closed_shipment(): void
    {
        $order = $this->makeOrder(['status' => StandardOrderStatus::ReturnedRefunded, 'raw_status' => 'returned']);
        $shipment = $this->makeShipment($order, ['status' => Shipment::STATUS_RETURNED]);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-lookup', ['code' => $shipment->tracking_no]);

        $resp->assertOk()->assertJsonPath('data.id', $order->getKey());
    }

    public function test_returns_404_when_code_unknown(): void
    {
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-lookup', ['code' => 'KHONG-TON-TAI']);

        $resp->assertStatus(404);
    }

    public function test_forbidden_for_role_without_fulfillment_permission(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $order = $this->makeOrder();
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($viewer)->withHeaders($this->h())
            ->postJson('/api/v1/scan-lookup', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(403);
    }
}
