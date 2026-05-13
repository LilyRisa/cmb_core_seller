<?php

namespace Tests\Feature\Procurement;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrder;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrderItem;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use CMBcoreSeller\Modules\Procurement\Models\SupplierPrice;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Phase 6.3 — Demand Planning. Velocity từ `order_costs` + on-order từ PO mở + safety stock + MOQ.
 */
class DemandPlanningTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Warehouse $wh;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop DP']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->wh = Warehouse::defaultFor((int) $this->tenant->getKey());
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeSku(string $code, int $costPrice = 0): Sku
    {
        return Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => $code, 'name' => $code,
            'cost_price' => $costPrice, 'is_active' => true,
        ]);
    }

    private function setStock(int $skuId, int $onHand, int $reserved = 0, int $safety = 0): void
    {
        // tạo level + adjust
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), $skuId, $this->wh->getKey(), $onHand);
        if ($reserved > 0 || $safety > 0) {
            $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
                ->where('sku_id', $skuId)->where('warehouse_id', $this->wh->getKey())->firstOrFail();
            $level->forceFill(['reserved' => $reserved, 'safety_stock' => $safety, 'available_cached' => max(0, $onHand - $reserved)])->save();
        }
    }

    private function recordShip(int $skuId, int $qty, Carbon $when): void
    {
        OrderCost::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => 0, 'order_item_id' => mt_rand(),
            'sku_id' => $skuId, 'qty' => $qty, 'cogs_unit_avg' => 100, 'cogs_total' => 100 * $qty,
            'cost_method' => 'fifo', 'shipped_at' => $when, 'created_at' => $when,
        ]);
    }

    public function test_velocity_and_suggested_qty_with_lead_time_and_cover(): void
    {
        $sku = $this->makeSku('FAST');
        $this->setStock($sku->getKey(), onHand: 20, reserved: 0, safety: 5);
        // Bán 5/ngày trong 10 ngày qua (50 đơn vị)
        for ($i = 0; $i < 10; $i++) {
            $this->recordShip($sku->getKey(), 5, Carbon::now()->subDays($i));
        }

        $r = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/procurement/demand-planning?window_days=30&lead_time=7&cover_days=14')
            ->assertOk()->json();
        $row = collect($r['data'])->firstWhere('sku.sku_code', 'FAST');
        $this->assertNotNull($row);
        // avg = 50/30 ≈ 1.667
        $this->assertEqualsWithDelta(1.667, (float) $row['avg_daily_sold'], 0.01);
        // available=20, days_left = 20/1.667 ≈ 11
        $this->assertSame(11, (int) $row['days_left']);
        // target = ceil(1.667*(7+14)) = ceil(35.007) = 36; needed = 36 - 20 - 0 = 16
        $this->assertSame(16, (int) $row['suggested_qty']);
        $this->assertSame('soon', $row['urgency']);
    }

    public function test_urgent_when_days_left_under_lead_time(): void
    {
        $sku = $this->makeSku('SLOW');
        $this->setStock($sku->getKey(), onHand: 2);
        for ($i = 0; $i < 10; $i++) {
            $this->recordShip($sku->getKey(), 3, Carbon::now()->subDays($i));   // 30/30 = 1/ngày
        }

        $r = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/procurement/demand-planning?window_days=30&lead_time=7&cover_days=14&urgency=urgent')
            ->assertOk()->json();
        $row = collect($r['data'])->firstWhere('sku.sku_code', 'SLOW');
        $this->assertNotNull($row);
        $this->assertSame('urgent', $row['urgency']);
        $this->assertSame(2, (int) $row['days_left']);
    }

    public function test_on_order_reduces_suggestion(): void
    {
        $sku = $this->makeSku('A');
        $this->setStock($sku->getKey(), onHand: 0);
        for ($i = 0; $i < 10; $i++) {
            $this->recordShip($sku->getKey(), 2, Carbon::now()->subDays($i));   // 20/30 ≈ 0.667
        }
        // PO confirmed mang về 10
        $supplier = Supplier::query()->create(['tenant_id' => $this->tenant->getKey(), 'code' => 'NCC-1', 'name' => 'NCC 1']);
        $po = PurchaseOrder::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'code' => 'PO-X', 'supplier_id' => $supplier->getKey(),
            'warehouse_id' => $this->wh->getKey(), 'status' => PurchaseOrder::STATUS_CONFIRMED,
            'total_qty' => 10, 'total_cost' => 0,
        ]);
        PurchaseOrderItem::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'purchase_order_id' => $po->getKey(),
            'sku_id' => $sku->getKey(), 'qty_ordered' => 10, 'qty_received' => 0, 'unit_cost' => 1000,
        ]);

        $r = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/procurement/demand-planning?window_days=30&lead_time=7&cover_days=14')
            ->assertOk()->json();
        $row = collect($r['data'])->firstWhere('sku.sku_code', 'A');
        $this->assertNotNull($row);
        $this->assertSame(10, (int) $row['on_order']);
        // target = ceil(0.667*21) = 15; needed = max(0, 15 - 0 - 10) = 5
        $this->assertSame(5, (int) $row['suggested_qty']);
    }

    public function test_create_po_from_suggestions(): void
    {
        $sku = $this->makeSku('A');
        $supplier = Supplier::query()->create(['tenant_id' => $this->tenant->getKey(), 'code' => 'NCC-Z', 'name' => 'NCC Z']);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/procurement/demand-planning/create-po', [
            'warehouse_id' => $this->wh->getKey(),
            'rows' => [
                ['sku_id' => $sku->getKey(), 'qty' => 20, 'supplier_id' => $supplier->getKey(), 'unit_cost' => 12000],
            ],
        ])->assertCreated();
        $this->assertSame(1, (int) $resp->json('data.count'));

        $po = PurchaseOrder::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->where('supplier_id', $supplier->getKey())->firstOrFail();
        $this->assertSame('draft', $po->status);
        $item = $po->items()->first();
        $this->assertSame(20, (int) $item->qty_ordered);
        $this->assertSame(12000, (int) $item->unit_cost);
    }

    public function test_supplier_default_moq_round_up(): void
    {
        $sku = $this->makeSku('B');
        $supplier = Supplier::query()->create(['tenant_id' => $this->tenant->getKey(), 'code' => 'NCC-MOQ', 'name' => 'NCC MOQ']);
        SupplierPrice::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'supplier_id' => $supplier->getKey(),
            'sku_id' => $sku->getKey(), 'unit_cost' => 8000, 'moq' => 10, 'is_default' => true,
        ]);
        $this->setStock($sku->getKey(), onHand: 0);
        // Bán 1/ngày × 30 ⇒ avg=1; target = 21; needed = 21 → round up MOQ 10 ⇒ 30
        for ($i = 0; $i < 30; $i++) {
            $this->recordShip($sku->getKey(), 1, Carbon::now()->subDays($i));
        }
        $r = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/procurement/demand-planning?window_days=30&lead_time=7&cover_days=14')
            ->assertOk()->json();
        $row = collect($r['data'])->firstWhere('sku.sku_code', 'B');
        $this->assertSame(30, (int) $row['suggested_qty']);
        $this->assertSame((int) $supplier->getKey(), (int) $row['suggested_supplier']['id']);
        $this->assertSame(8000, (int) $row['suggested_unit_cost']);
    }
}
