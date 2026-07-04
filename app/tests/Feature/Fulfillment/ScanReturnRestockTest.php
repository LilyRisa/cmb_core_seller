<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** POST /scan-return-restock — quét đơn hoàn ⇒ cộng hàng trả về tồn kho (idempotent). */
class ScanReturnRestockTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop restock']);
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
            'order_number' => 'RS-'.uniqid(),
            'status' => StandardOrderStatus::ReturnedRefunded,
            'raw_status' => 'returned',
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

    private function makeSku(): Sku
    {
        return Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'sku_code' => 'SKU-'.uniqid(),
            'name' => 'Áo thun',
        ]);
    }

    private function makeItem(Order $order, ?int $skuId, int $qty = 2): OrderItem
    {
        return OrderItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'external_item_id' => 'EXT-'.uniqid(),
            'external_product_id' => 'EXT-PROD',
            'external_sku_id' => 'EXT-SKU',
            'seller_sku' => 'SELLER-SKU',
            'sku_id' => $skuId,
            'name' => 'Áo thun',
            'quantity' => $qty,
            'unit_price' => 50000,
            'subtotal' => 50000 * $qty,
        ]);
    }

    private function makeShipment(Order $order, array $overrides = []): Shipment
    {
        return Shipment::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'carrier' => 'manual',
            'tracking_no' => 'RS-TN-'.uniqid(),
            'status' => Shipment::STATUS_RETURNED,
        ], $overrides));
    }

    private function onHand(int $skuId): int
    {
        return (int) (InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->where('sku_id', $skuId)->sum('on_hand'));
    }

    public function test_restocks_mapped_items_of_returned_order(): void
    {
        $order = $this->makeOrder();
        $sku = $this->makeSku();
        $this->makeItem($order, $sku->getKey(), qty: 3);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-return-restock', ['code' => $shipment->tracking_no]);

        $resp->assertOk()
            ->assertJsonPath('data.order_id', $order->getKey())
            ->assertJsonPath('data.restocked_lines', 1)
            ->assertJsonPath('data.skipped_unmapped', 0)
            ->assertJsonPath('data.already', false)
            ->assertJsonPath('data.lines.0.restocked', true);

        $this->assertSame(3, $this->onHand($sku->getKey()), 'Tồn phải tăng đúng số lượng hoàn.');
    }

    public function test_is_idempotent_no_double_count_on_rescan(): void
    {
        $order = $this->makeOrder();
        $sku = $this->makeSku();
        $this->makeItem($order, $sku->getKey(), qty: 3);
        $shipment = $this->makeShipment($order);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-return-restock', ['code' => $shipment->tracking_no])->assertOk();

        // Quét lại lần 2 — KHÔNG cộng trùng.
        $resp2 = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-return-restock', ['code' => $shipment->tracking_no]);

        $resp2->assertOk()
            ->assertJsonPath('data.restocked_lines', 0)
            ->assertJsonPath('data.already', true);

        $this->assertSame(3, $this->onHand($sku->getKey()), 'Quét lại KHÔNG được cộng trùng tồn.');
        $this->assertSame(1, InventoryMovement::withoutGlobalScope(TenantScope::class)
            ->where('sku_id', $sku->getKey())->where('type', InventoryMovement::RETURN_IN)->count(),
            'Chỉ được 1 movement return_in cho mỗi (đơn, sku).');
    }

    public function test_skips_unmapped_items(): void
    {
        $order = $this->makeOrder();
        $this->makeItem($order, null, qty: 2); // chưa ghép SKU
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-return-restock', ['code' => $shipment->tracking_no]);

        $resp->assertOk()
            ->assertJsonPath('data.restocked_lines', 0)
            ->assertJsonPath('data.skipped_unmapped', 1)
            ->assertJsonPath('data.lines.0.restocked', false)
            ->assertJsonPath('data.lines.0.mapped', false);
    }

    public function test_blocks_when_order_not_returned(): void
    {
        $order = $this->makeOrder(['status' => StandardOrderStatus::Processing, 'raw_status' => 'processing']);
        $sku = $this->makeSku();
        $this->makeItem($order, $sku->getKey());
        // shipment vẫn CREATED (không hoàn) — không đủ điều kiện.
        $shipment = $this->makeShipment($order, ['status' => Shipment::STATUS_CREATED]);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-return-restock', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)->assertJsonPath('code', 'not_returned');
        $this->assertSame(0, $this->onHand($sku->getKey()), 'Đơn chưa hoàn KHÔNG được cộng tồn.');
    }

    public function test_returns_404_when_code_unknown(): void
    {
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-return-restock', ['code' => 'KHONG-TON-TAI']);

        $resp->assertStatus(404);
    }

    public function test_forbidden_for_role_without_inventory_adjust(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $order = $this->makeOrder();
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($viewer)->withHeaders($this->h())
            ->postJson('/api/v1/scan-return-restock', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(403);
    }
}
