<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Reproduces the prod incident: the idempotent-movement unique index migration
 * (2026_07_23_120000) already ran cleanly during RefreshDatabase (empty DB, nothing to dedupe).
 * To verify its dedupe-and-correct logic against MESSY historical data (like real prod had), this
 * test drops the index it just created, seeds duplicate rows + wrong on_hand/reserved (mirroring
 * what a racy InventoryLedgerService::apply() would have produced), then re-runs the migration's
 * up() directly and asserts the data is corrected and the index is back in place.
 */
class DedupeInventoryMovementsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $skuId;

    private int $warehouseId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('DROP INDEX IF EXISTS inventory_movements_idempotent_unique');

        $tenant = Tenant::create(['name' => 'Shop']);
        $this->tenantId = (int) $tenant->getKey();
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenantId, 'sku_code' => 'SKU-1', 'name' => 'Áo']);
        $this->skuId = (int) $sku->getKey();
    }

    private function insertMovement(string $type, string $refId, int $qtyChange): void
    {
        DB::table('inventory_movements')->insert([
            'tenant_id' => $this->tenantId, 'sku_id' => $this->skuId, 'warehouse_id' => $this->warehouseId,
            'type' => $type, 'ref_type' => 'order_item', 'ref_id' => $refId, 'qty_change' => $qtyChange,
            'balance_after' => 0, 'created_at' => now(),
        ]);
    }

    private function level(): object
    {
        return DB::table('inventory_levels')->where('sku_id', $this->skuId)->where('warehouse_id', $this->warehouseId)->first();
    }

    private function runMigration(): void
    {
        (require app_path('Modules/Inventory/Database/Migrations/2026_07_23_120000_add_idempotent_movement_unique_index.php'))->up();
    }

    public function test_dedupes_double_release_and_recomputes_reserved(): void
    {
        // Mirrors the real prod incident: order_item 25456 reserved (qty 1) then released TWICE
        // (race) — reserved ends up at -1 instead of 0, on_hand untouched since release doesn't
        // touch it.
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantId, 'sku_id' => $this->skuId, 'warehouse_id' => $this->warehouseId,
            'on_hand' => 5, 'reserved' => -1, 'safety_stock' => 0, 'available_cached' => 6, 'is_negative' => false,
        ]);
        $this->insertMovement('order_reserve', '25456', 1);
        $this->insertMovement('order_release', '25456', -1);
        $this->insertMovement('order_release', '25456', -1); // erroneous duplicate (the race)

        $this->assertSame(3, DB::table('inventory_movements')->count());

        $this->runMigration();

        $this->assertSame(2, DB::table('inventory_movements')->count(), 'Dòng order_release lặp phải bị xoá.');
        $level = $this->level();
        $this->assertSame(5, $level->on_hand, 'order_release không đụng on_hand.');
        $this->assertSame(0, $level->reserved, 'Đã release xong ⇒ không còn giữ chỗ nào.');
        $this->assertFalse((bool) $level->is_negative);
    }

    public function test_dedupes_double_ship_and_reverses_on_hand_overcount(): void
    {
        // order_item 5376 shipped TWICE (race) — on_hand was decremented by qty (1) twice instead
        // of once, reserved consumed via hadOpenReservation=true both times (mirrors real prod row
        // sku_id=3/ref_id=5376/order_ship — the actual failing key from the migration error).
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantId, 'sku_id' => $this->skuId, 'warehouse_id' => $this->warehouseId,
            'on_hand' => 3, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => 3, 'is_negative' => false,
        ]);
        $this->insertMovement('order_reserve', '5376', 1);
        $this->insertMovement('order_ship', '5376', -1);
        $this->insertMovement('order_ship', '5376', -1); // erroneous duplicate

        $this->runMigration();

        $this->assertSame(2, DB::table('inventory_movements')->count());
        $level = $this->level();
        // on_hand had the extra -1 double-applied — corrected back by +1.
        $this->assertSame(4, $level->on_hand, 'Dòng order_ship lặp đã trừ dư 1 on_hand — phải hoàn lại.');
        $this->assertSame(0, $level->reserved, 'Đã ship ⇒ hết giữ chỗ.');
    }

    public function test_ignores_groups_of_one_and_non_idempotent_types(): void
    {
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantId, 'sku_id' => $this->skuId, 'warehouse_id' => $this->warehouseId,
            'on_hand' => 10, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => 10, 'is_negative' => false,
        ]);
        $this->insertMovement('order_reserve', '900', 1);
        // goods_issue is NOT one of the 4 idempotent types — legitimately repeated calls must survive.
        DB::table('inventory_movements')->insert([
            ['tenant_id' => $this->tenantId, 'sku_id' => $this->skuId, 'warehouse_id' => $this->warehouseId,
                'type' => 'goods_issue', 'ref_type' => 'goods_issue', 'ref_id' => 42, 'qty_change' => -1, 'balance_after' => 9, 'created_at' => now()],
            ['tenant_id' => $this->tenantId, 'sku_id' => $this->skuId, 'warehouse_id' => $this->warehouseId,
                'type' => 'goods_issue', 'ref_type' => 'goods_issue', 'ref_id' => 42, 'qty_change' => -1, 'balance_after' => 8, 'created_at' => now()],
        ]);

        $this->runMigration();

        $this->assertSame(3, DB::table('inventory_movements')->count(), 'goods_issue lặp KHÔNG được đụng tới.');
    }

    public function test_index_rejects_future_duplicates_after_migration(): void
    {
        $this->runMigration();

        $this->insertMovement('order_reserve', '111', 1);
        $this->expectException(UniqueConstraintViolationException::class);
        $this->insertMovement('order_reserve', '111', 1);
    }
}
