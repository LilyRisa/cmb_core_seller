<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\CostLayer;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceiptItem;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * FIFO cost layers + order_costs — Phase 6.1 / SPEC 0014.
 *
 * Scenario kế toán chuẩn: 2 lô nhập 10 @1000 + 10 @1500 → bán 5 → COGS 5000 (1000×5);
 * bán tiếp 10 → COGS = 5×1000 + 5×1500 = 12500; bán nốt → COGS = 5×1500 + 5×synthetic.
 */
class FifoCostTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private Warehouse $wh;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop FIFO']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->wh = Warehouse::defaultFor((int) $this->tenant->getKey());
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A', 'name' => 'Áo M']);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 0);   // ensure level row
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    /** Tạo + confirm 1 phiếu nhập (1 dòng) → 1 cost layer mới. */
    private function receiveBatch(int $qty, int $unitCost): GoodsReceipt
    {
        $doc = GoodsReceipt::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'code' => 'PNK-'.uniqid(),
            'warehouse_id' => $this->wh->getKey(), 'status' => GoodsReceipt::STATUS_DRAFT,
        ]);
        GoodsReceiptItem::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'goods_receipt_id' => $doc->getKey(),
            'sku_id' => $this->sku->getKey(), 'qty' => $qty, 'unit_cost' => $unitCost,
        ]);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/goods-receipts/{$doc->getKey()}/confirm")->assertOk();

        return $doc->fresh();
    }

    public function test_fifo_consume_oldest_first_with_two_layers(): void
    {
        $b1 = $this->receiveBatch(10, 1000);
        sleep(1);   // tách `received_at` thứ tự cho FIFO ổn định
        $b2 = $this->receiveBatch(10, 1500);
        $this->assertSame(2, CostLayer::withoutGlobalScope(TenantScope::class)->where('sku_id', $this->sku->getKey())->count());

        // Đơn 1: bán 5 — chỉ rút từ layer 1 (1000)
        $order1 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'A', 'phone' => '0900000001'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 5, 'unit_price' => 200000]],
        ])->assertCreated()->json('data.id');
        $shId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$order1}/ship", ['tracking_no' => 'T1'])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/shipments/{$shId}/track");   // no-op
        // mark packed → handover ⇒ shipped (trừ tồn + ghi order_cost)
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shId]])->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/handover', ['shipment_ids' => [$shId]])->assertOk();

        $oc1 = OrderCost::withoutGlobalScope(TenantScope::class)->where('order_id', $order1)->firstOrFail();
        $this->assertSame(5000, (int) $oc1->cogs_total);
        $this->assertSame(1000, (int) $oc1->cogs_unit_avg);
        $this->assertSame('fifo', $oc1->cost_method);
        $this->assertCount(1, $oc1->layers_used);
        $this->assertSame(1000, (int) $oc1->layers_used[0]['unit_cost']);

        // Đơn 2: bán 10 — rút 5×1000 còn lại + 5×1500
        $order2 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'B', 'phone' => '0900000002'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 10, 'unit_price' => 200000]],
        ])->assertCreated()->json('data.id');
        $shId2 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$order2}/ship", ['tracking_no' => 'T2'])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shId2]])->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/handover', ['shipment_ids' => [$shId2]])->assertOk();

        $oc2 = OrderCost::withoutGlobalScope(TenantScope::class)->where('order_id', $order2)->firstOrFail();
        $this->assertSame(5 * 1000 + 5 * 1500, (int) $oc2->cogs_total);
        $this->assertCount(2, $oc2->layers_used);

        // Layer 1 exhausted, layer 2 còn 5
        $layer1 = CostLayer::withoutGlobalScope(TenantScope::class)->where('source_id', $b1->getKey())->firstOrFail();
        $layer2 = CostLayer::withoutGlobalScope(TenantScope::class)->where('source_id', $b2->getKey())->firstOrFail();
        $this->assertSame(0, (int) $layer1->qty_remaining);
        $this->assertNotNull($layer1->exhausted_at);
        $this->assertSame(5, (int) $layer2->qty_remaining);
        // _ b2 ref to silence unused-var lint
        unset($b2);
    }

    public function test_order_resource_profit_uses_actual_cogs_when_shipped(): void
    {
        $this->receiveBatch(10, 1000);
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'C', 'phone' => '0900000003'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 2, 'unit_price' => 150000]],
        ])->assertCreated()->json('data.id');
        // Trước khi ship: cost_source = estimate (vẫn dùng Sku.cost_price = 1000 từ recordReceiptCost)
        $before = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/orders/{$orderId}")->assertOk()->json('data.profit');
        $this->assertSame('estimate', $before['cost_source']);

        // Ship → ghi order_cost với cost_source=fifo
        $shId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/ship", ['tracking_no' => 'T3'])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shId]])->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/handover', ['shipment_ids' => [$shId]])->assertOk();

        $after = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/orders/{$orderId}")->assertOk()->json('data.profit');
        $this->assertSame('fifo', $after['cost_source']);
        $this->assertSame(2000, (int) $after['cogs']);
        $this->assertTrue((bool) $after['cost_complete']);
    }
}
