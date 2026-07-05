<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_decreases_on_hand_and_writes_movement(): void
    {
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'sku_id' => 7, 'warehouse_id' => 2,
            'on_hand' => 10, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => 10,
        ]);

        $mv = app(InventoryLedgerService::class)->issue(1, 7, 2, 4, 'Xuất kho PXK-x', 'goods_issue', 99, 1);

        $this->assertSame(InventoryMovement::GOODS_ISSUE, $mv->type);
        $this->assertSame(-4, (int) $mv->qty_change);
        $this->assertSame(6, (int) $mv->balance_after);

        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', 1)->where('sku_id', 7)->where('warehouse_id', 2)->first();
        $this->assertSame(6, (int) $level->on_hand);
    }
}
