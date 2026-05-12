<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Sku $sku;

    private InventoryLedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'SKU-1', 'name' => 'Áo']);
        $this->ledger = app(InventoryLedgerService::class);
    }

    private function level(): InventoryLevel
    {
        return InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $this->sku->getKey())->firstOrFail();
    }

    public function test_adjust_writes_a_movement_and_creates_default_warehouse(): void
    {
        $m = $this->ledger->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 25, 'Nhập đầu kỳ');
        $this->assertSame(25, $m->balance_after);
        $this->assertSame(InventoryMovement::MANUAL_ADJUST, $m->type);

        $level = $this->level();
        $this->assertSame(25, $level->on_hand);
        $this->assertSame(25, $level->available_cached);
        $this->assertTrue(Warehouse::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->where('is_default', true)->exists());
    }

    public function test_reserve_release_ship_lifecycle_and_idempotency(): void
    {
        $tid = (int) $this->tenant->getKey();
        $sid = (int) $this->sku->getKey();
        $this->ledger->adjust($tid, $sid, null, 10);

        // reserve 3 (twice — second is a no-op)
        $this->ledger->reserve($tid, $sid, 3, 'order_item', 100);
        $this->assertNull($this->ledger->reserve($tid, $sid, 3, 'order_item', 100));   // idempotent
        $level = $this->level();
        $this->assertSame(10, $level->on_hand);
        $this->assertSame(3, $level->reserved);
        $this->assertSame(7, $level->available_cached);
        $this->assertTrue($this->ledger->hasOpenReservation($tid, $sid, 'order_item', 100));

        // ship 3 (consume the reservation), idempotent
        $this->ledger->ship($tid, $sid, 3, 'order_item', 100, hadOpenReservation: true);
        $this->assertNull($this->ledger->ship($tid, $sid, 3, 'order_item', 100, true));
        $level = $this->level();
        $this->assertSame(7, $level->on_hand);
        $this->assertSame(0, $level->reserved);
        $this->assertSame(7, $level->available_cached);
        $this->assertFalse($this->ledger->hasOpenReservation($tid, $sid, 'order_item', 100));

        // a different order line: reserve then release
        $this->ledger->reserve($tid, $sid, 2, 'order_item', 200);
        $this->ledger->release($tid, $sid, 2, 'order_item', 200);
        $this->assertSame(7, $this->level()->on_hand);
        $this->assertSame(0, $this->level()->reserved);

        $count = InventoryMovement::withoutGlobalScope(TenantScope::class)->where('sku_id', $sid)->count();
        $this->assertSame(5, $count);   // adjust + reserve(100) + ship(100) + reserve(200) + release(200)
    }

    public function test_overselling_flags_negative_and_zeroes_available(): void
    {
        $tid = (int) $this->tenant->getKey();
        $sid = (int) $this->sku->getKey();
        $this->ledger->adjust($tid, $sid, null, 1);
        $this->ledger->reserve($tid, $sid, 5, 'order_item', 1);   // sold beyond stock
        $level = $this->level();
        $this->assertSame(1, $level->on_hand);
        $this->assertSame(5, $level->reserved);
        $this->assertSame(0, $level->available_cached);   // clamped
        $this->assertTrue($level->is_negative);
    }
}
