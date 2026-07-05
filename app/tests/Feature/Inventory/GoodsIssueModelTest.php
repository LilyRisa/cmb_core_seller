<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssueItem;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsIssueModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_goods_issue_persists_with_items(): void
    {
        $doc = GoodsIssue::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'code' => 'PXK-260705-ABCDE', 'warehouse_id' => 1,
            'reason' => 'Hàng hỏng', 'status' => GoodsIssue::STATUS_DRAFT, 'created_by' => 1,
        ]);
        $item = GoodsIssueItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'goods_issue_id' => $doc->id, 'sku_id' => 7, 'qty' => 3,
        ]);

        $this->assertSame('draft', $doc->fresh()->status);
        $this->assertSame(1, $doc->items()->withoutGlobalScope(TenantScope::class)->count());
        $this->assertSame(3, (int) $item->qty);
        $this->assertSame('goods_issue', InventoryMovement::GOODS_ISSUE);
    }
}
