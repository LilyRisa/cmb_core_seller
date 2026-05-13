<?php

namespace Tests\Feature\Procurement;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrder;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $a;

    private Sku $b;

    private Warehouse $wh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->wh = Warehouse::defaultFor((int) $this->tenant->getKey());
        $this->a = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A', 'name' => 'A']);
        $this->b = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'B', 'name' => 'B']);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_create_confirm_receive_in_batches_and_cancel(): void
    {
        $supplier = Supplier::query()->create(['tenant_id' => $this->tenant->getKey(), 'code' => 'NCC-1', 'name' => 'NCC 1']);

        // Tạo PO 2 dòng
        $po = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->getKey(), 'warehouse_id' => $this->wh->getKey(),
            'expected_at' => '2026-06-01', 'note' => 'PO test',
            'items' => [
                ['sku_id' => $this->a->getKey(), 'qty_ordered' => 10, 'unit_cost' => 1000],
                ['sku_id' => $this->b->getKey(), 'qty_ordered' => 4, 'unit_cost' => 5000],
            ],
        ])->assertCreated();
        $poId = (int) $po->json('data.id');
        $po->assertJsonPath('data.status', 'draft')->assertJsonPath('data.total_qty', 14)->assertJsonPath('data.total_cost', 10000 + 20000);

        // Confirm
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$poId}/confirm")
            ->assertOk()->assertJsonPath('data.status', 'confirmed');

        // Đợt 1: nhận 50% SKU A → tạo GoodsReceipt draft → confirm qua WMS
        $r1 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$poId}/receive", [
            'lines' => [['sku_id' => $this->a->getKey(), 'qty' => 5]],
        ])->assertCreated();
        $gr1Id = (int) $r1->json('data.goods_receipt.id');
        // Confirm phiếu nhập
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/goods-receipts/{$gr1Id}/confirm")->assertOk();
        // PO → partially_received; A.qty_received = 5, B = 0
        $detail = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/purchase-orders/{$poId}")->assertOk();
        $detail->assertJsonPath('data.status', 'partially_received')->assertJsonPath('data.progress_percent', (int) round(5 * 100 / 14));

        // Đợt 2: nhận đủ phần còn lại
        $r2 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$poId}/receive", [
            'lines' => [['sku_id' => $this->a->getKey(), 'qty' => 5], ['sku_id' => $this->b->getKey(), 'qty' => 4]],
        ])->assertCreated();
        $gr2Id = (int) $r2->json('data.goods_receipt.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/warehouse-docs/goods-receipts/{$gr2Id}/confirm")->assertOk();
        // PO → received
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/purchase-orders/{$poId}")
            ->assertOk()->assertJsonPath('data.status', 'received')->assertJsonPath('data.progress_percent', 100);

        // Receipt liên kết PO
        $this->assertSame($poId, (int) GoodsReceipt::withoutGlobalScope(TenantScope::class)->find($gr1Id)->purchase_order_id);
        $this->assertSame($poId, (int) GoodsReceipt::withoutGlobalScope(TenantScope::class)->find($gr2Id)->purchase_order_id);
    }

    public function test_cancel_only_when_draft_and_validation(): void
    {
        $supplier = Supplier::query()->create(['tenant_id' => $this->tenant->getKey(), 'code' => 'NCC-2', 'name' => 'NCC 2']);
        $po = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->getKey(), 'warehouse_id' => $this->wh->getKey(),
            'items' => [['sku_id' => $this->a->getKey(), 'qty_ordered' => 10, 'unit_cost' => 1000]],
        ])->assertCreated()->json('data.id');

        // Huỷ ở draft → OK
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$po}/cancel")
            ->assertOk()->assertJsonPath('data.status', 'cancelled');

        // PO mới — confirm rồi huỷ ⇒ 422
        $po2 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->getKey(), 'warehouse_id' => $this->wh->getKey(),
            'items' => [['sku_id' => $this->a->getKey(), 'qty_ordered' => 5, 'unit_cost' => 1000]],
        ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$po2}/confirm")->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$po2}/cancel")->assertStatus(422);

        // Receive vượt số còn lại ⇒ 422
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$po2}/receive", [
            'lines' => [['sku_id' => $this->a->getKey(), 'qty' => 99]],
        ])->assertStatus(422);
    }

    public function test_warehouse_staff_can_receive_but_not_manage(): void
    {
        $wh = User::factory()->create();
        $this->tenant->users()->attach($wh->getKey(), ['role' => Role::StaffWarehouse->value]);

        $supplier = Supplier::query()->create(['tenant_id' => $this->tenant->getKey(), 'code' => 'NCC-3', 'name' => 'NCC 3']);
        $po = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->getKey(), 'warehouse_id' => $this->wh->getKey(),
            'items' => [['sku_id' => $this->a->getKey(), 'qty_ordered' => 3, 'unit_cost' => 1000]],
        ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$po}/confirm")->assertOk();

        // Kho không tạo/sửa được PO
        $this->actingAs($wh)->withHeaders($this->h())->postJson('/api/v1/purchase-orders', [])->assertForbidden();
        $this->actingAs($wh)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$po}/cancel")->assertForbidden();
        // Nhưng nhận hàng được
        $this->actingAs($wh)->withHeaders($this->h())->postJson("/api/v1/purchase-orders/{$po}/receive", [
            'lines' => [['sku_id' => $this->a->getKey(), 'qty' => 3]],
        ])->assertCreated();
    }
}
