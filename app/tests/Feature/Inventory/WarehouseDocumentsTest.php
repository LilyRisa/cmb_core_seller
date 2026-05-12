<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/** Phase 5 WMS — phiếu nhập kho / chuyển kho / kiểm kê (SPEC 0010). */
class WarehouseDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private Warehouse $wh1;

    private Warehouse $wh2;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->wh1 = Warehouse::defaultFor((int) $this->tenant->getKey());
        $this->wh2 = Warehouse::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Kho 2', 'code' => 'WH2']);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'SKU-1', 'name' => 'Áo', 'cost_price' => 40000]);
        // initial stock in wh1: 10 @ cost 40000
        app(InventoryLedgerService::class)->receipt((int) $this->tenant->getKey(), (int) $this->sku->getKey(), (int) $this->wh1->getKey(), 10);
        InventoryLevel::withoutGlobalScope(TenantScope::class)->where('warehouse_id', $this->wh1->getKey())->where('sku_id', $this->sku->getKey())->update(['cost_price' => 40000]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function level(int $whId): InventoryLevel
    {
        return InventoryLevel::withoutGlobalScope(TenantScope::class)->where('warehouse_id', $whId)->where('sku_id', $this->sku->getKey())->firstOrFail();
    }

    public function test_goods_receipt_draft_confirm_updates_stock_and_average_cost(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/warehouse-docs/goods-receipts', [
            'warehouse_id' => $this->wh1->getKey(), 'supplier' => 'NCC A', 'note' => 'Nhập đầu kỳ',
            'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 30, 'unit_cost' => 50000]],
        ])->assertCreated();
        $res->assertJsonPath('data.status', 'draft')->assertJsonPath('data.type', 'goods-receipts')->assertJsonPath('data.items.0.qty', 30);
        $id = (int) $res->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/goods-receipts/{$id}/confirm")->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertSame(40, $this->level((int) $this->wh1->getKey())->on_hand);   // 10 + 30
        // weighted average cost: (10*40000 + 30*50000) / 40 = 47500
        $this->assertSame(47500, $this->level((int) $this->wh1->getKey())->cost_price);
        $this->assertTrue(InventoryMovement::withoutGlobalScope(TenantScope::class)->where('sku_id', $this->sku->getKey())->where('type', 'goods_receipt')->where('ref_type', 'goods_receipt')->where('ref_id', $id)->exists());

        // confirmed phiếu is immutable
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/goods-receipts/{$id}/confirm")->assertStatus(422);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/goods-receipts/{$id}/cancel")->assertStatus(422);
    }

    public function test_cancel_draft_goods_receipt(): void
    {
        $id = (int) $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/warehouse-docs/goods-receipts', [
            'warehouse_id' => $this->wh1->getKey(), 'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 5, 'unit_cost' => 1]],
        ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/goods-receipts/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(10, $this->level((int) $this->wh1->getKey())->on_hand);   // untouched
        $this->assertSame('cancelled', GoodsReceipt::withoutGlobalScope(TenantScope::class)->find($id)->status);
    }

    public function test_stock_transfer_moves_stock_between_warehouses(): void
    {
        $id = (int) $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/warehouse-docs/stock-transfers', [
            'from_warehouse_id' => $this->wh1->getKey(), 'to_warehouse_id' => $this->wh2->getKey(),
            'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 4]],
        ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/stock-transfers/{$id}/confirm")->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertSame(6, $this->level((int) $this->wh1->getKey())->on_hand);    // 10 - 4
        $this->assertSame(4, $this->level((int) $this->wh2->getKey())->on_hand);    // 0 + 4
        $this->assertTrue(InventoryMovement::withoutGlobalScope(TenantScope::class)->where('type', 'transfer_out')->where('ref_id', $id)->exists());
        $this->assertTrue(InventoryMovement::withoutGlobalScope(TenantScope::class)->where('type', 'transfer_in')->where('ref_id', $id)->exists());

        // same source = destination → 422 at create
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/warehouse-docs/stock-transfers', [
            'from_warehouse_id' => $this->wh1->getKey(), 'to_warehouse_id' => $this->wh1->getKey(),
            'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 1]],
        ])->assertStatus(422);
    }

    public function test_stocktake_snapshots_system_qty_and_adjusts_on_confirm(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/warehouse-docs/stocktakes', [
            'warehouse_id' => $this->wh1->getKey(), 'note' => 'Kiểm kê tháng 5',
            'items' => [['sku_id' => $this->sku->getKey(), 'counted_qty' => 7]],
        ])->assertCreated();
        $res->assertJsonPath('data.items.0.system_qty', 10)->assertJsonPath('data.items.0.counted_qty', 7)->assertJsonPath('data.items.0.diff', -3);
        $id = (int) $res->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/stocktakes/{$id}/confirm")->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->assertSame(7, $this->level((int) $this->wh1->getKey())->on_hand);    // 10 → 7
        $this->assertTrue(InventoryMovement::withoutGlobalScope(TenantScope::class)->where('type', 'stocktake_adjust')->where('ref_id', $id)->where('qty_change', -3)->exists());
    }

    public function test_rbac_and_tenant_isolation(): void
    {
        // viewer can't create a goods receipt
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->getJson('/api/v1/warehouse-docs/goods-receipts')->assertOk();    // can view
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/warehouse-docs/goods-receipts', ['warehouse_id' => $this->wh1->getKey(), 'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 1]]])->assertForbidden();

        // a doc from another tenant → 404
        $other = Tenant::create(['name' => 'B']);
        $otherDoc = GoodsReceipt::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $other->getKey(), 'code' => 'X', 'warehouse_id' => 1, 'status' => 'draft']);
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/warehouse-docs/goods-receipts/{$otherDoc->getKey()}")->assertNotFound();
        // unknown {type} → 404
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/warehouse-docs/nope')->assertNotFound();
    }
}
