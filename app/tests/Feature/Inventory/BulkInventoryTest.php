<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BulkInventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Tenant $other;

    private Sku $a;

    private Sku $b;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->other = Tenant::create(['name' => 'Shop B']);
        $this->a = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A', 'name' => 'A']);
        $this->b = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'B', 'name' => 'B']);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function level(Sku $s): InventoryLevel
    {
        return InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $s->getKey())->firstOrFail();
    }

    public function test_bulk_receipt_writes_one_movement_per_line(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', [
            'kind' => 'goods_receipt', 'note' => 'Nhập đầu kỳ',
            'lines' => [['sku_id' => $this->a->getKey(), 'qty_change' => 50], ['sku_id' => $this->b->getKey(), 'qty_change' => 20]],
        ])->assertCreated();
        $this->assertSame(2, $res->json('data.applied'));
        $this->assertSame(50, $this->level($this->a)->on_hand);
        $this->assertSame(20, $this->level($this->b)->on_hand);
        $this->assertSame('goods_receipt', $res->json('data.movements.0.type'));
        $this->assertSame('manual_bulk', $res->json('data.movements.0.ref_type'));
        Bus::assertDispatched(PushStockForSku::class);
    }

    public function test_bulk_manual_adjust_allows_negative_and_records_balance(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', [
            'kind' => 'goods_receipt', 'lines' => [['sku_id' => $this->a->getKey(), 'qty_change' => 10]],
        ])->assertCreated();
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', [
            'kind' => 'manual_adjust', 'note' => 'Kiểm kê',
            'lines' => [['sku_id' => $this->a->getKey(), 'qty_change' => -4]],
        ])->assertCreated();
        $this->assertSame(6, $this->level($this->a)->on_hand);
        $this->assertSame(6, $res->json('data.movements.0.balance_after'));
    }

    public function test_bulk_adjust_rejects_invalid_input(): void
    {
        // duplicate sku
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', [
            'kind' => 'goods_receipt', 'lines' => [['sku_id' => $this->a->getKey(), 'qty_change' => 5], ['sku_id' => $this->a->getKey(), 'qty_change' => 3]],
        ])->assertStatus(422);
        // negative qty on a receipt
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', [
            'kind' => 'goods_receipt', 'lines' => [['sku_id' => $this->a->getKey(), 'qty_change' => -1]],
        ])->assertStatus(422);
        // sku of another tenant
        $otherSku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->other->getKey(), 'sku_code' => 'OTH', 'name' => 'oth']);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', [
            'kind' => 'goods_receipt', 'lines' => [['sku_id' => $otherSku->getKey(), 'qty_change' => 1]],
        ])->assertStatus(422);
        // qty 0
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', [
            'kind' => 'manual_adjust', 'lines' => [['sku_id' => $this->a->getKey(), 'qty_change' => 0]],
        ])->assertStatus(422);
    }

    public function test_bulk_push_dispatches_per_valid_sku(): void
    {
        $otherSku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->other->getKey(), 'sku_code' => 'OTH', 'name' => 'oth']);
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/push-stock', [
            'sku_ids' => [$this->a->getKey(), $this->b->getKey(), $otherSku->getKey()],
        ])->assertOk();
        $this->assertSame(2, $res->json('data.queued'));   // other-tenant SKU dropped
        Bus::assertDispatched(PushStockForSku::class, fn ($j) => $j->skuId === (int) $this->a->getKey());
        Bus::assertNotDispatched(PushStockForSku::class, fn ($j) => $j->skuId === (int) $otherSku->getKey());
    }

    public function test_viewer_cannot_bulk_adjust_or_push(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/inventory/bulk-adjust', ['kind' => 'goods_receipt', 'lines' => [['sku_id' => $this->a->getKey(), 'qty_change' => 1]]])->assertForbidden();
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/inventory/push-stock', ['sku_ids' => [$this->a->getKey()]])->assertForbidden();
    }
}
