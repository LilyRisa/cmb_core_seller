<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Events\GoodsIssueConfirmed;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssueItem;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Services\WarehouseDocumentService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConfirmGoodsIssueTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        // Direct service calls (no HTTP/EnsureTenant middleware) need CurrentTenant set explicitly,
        // else the global TenantScope constrains $doc->items to tenant_id=0 (see StockPushLogTest pattern).
        $tenant = Tenant::create(['name' => 'Shop']);
        app(CurrentTenant::class)->set($tenant);
        $this->tenantId = (int) $tenant->getKey();
    }

    private function seedLevel(int $onHand): void
    {
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantId, 'sku_id' => 7, 'warehouse_id' => 2,
            'on_hand' => $onHand, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => $onHand,
        ]);
    }

    private function draft(int $qty): GoodsIssue
    {
        $doc = GoodsIssue::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantId, 'code' => 'PXK-260705-AAAAA', 'warehouse_id' => 2,
            'status' => GoodsIssue::STATUS_DRAFT, 'created_by' => 1,
        ]);
        GoodsIssueItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantId, 'goods_issue_id' => $doc->id, 'sku_id' => 7, 'qty' => $qty,
        ]);

        return $doc;
    }

    public function test_confirm_decreases_stock_and_dispatches_event(): void
    {
        Event::fake([GoodsIssueConfirmed::class]);
        $this->seedLevel(10);
        $doc = $this->draft(4);

        $out = app(WarehouseDocumentService::class)->confirmGoodsIssue($doc, 1);

        $this->assertSame('confirmed', $out->status);
        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenantId)->where('sku_id', 7)->where('warehouse_id', 2)->first();
        $this->assertSame(6, (int) $level->on_hand);
        $this->assertSame(1, InventoryMovement::withoutGlobalScope(TenantScope::class)
            ->where('type', 'goods_issue')->where('ref_id', $doc->id)->count());
        Event::assertDispatched(GoodsIssueConfirmed::class);
    }

    public function test_confirm_blocks_when_exceeds_on_hand(): void
    {
        $this->seedLevel(3);
        $doc = $this->draft(5);

        $this->expectException(\RuntimeException::class);
        try {
            app(WarehouseDocumentService::class)->confirmGoodsIssue($doc, 1);
        } finally {
            $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $this->tenantId)->where('sku_id', 7)->where('warehouse_id', 2)->first();
            $this->assertSame(3, (int) $level->on_hand);
            $this->assertSame('draft', $doc->fresh()->status);
        }
    }

    public function test_confirm_twice_is_rejected(): void
    {
        $this->seedLevel(10);
        $doc = $this->draft(2);
        app(WarehouseDocumentService::class)->confirmGoodsIssue($doc, 1);

        $this->expectException(\RuntimeException::class);
        app(WarehouseDocumentService::class)->confirmGoodsIssue($doc->fresh(), 1);
    }
}
