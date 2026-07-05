<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Phiếu xuất kho (goods-issues) API — draft/confirm decrement tồn + chặn xuất SKU/kho khác gian hàng. */
class GoodsIssueApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
    }

    /**
     * Seed a fresh tenant + owner user (has inventory.adjust via Role::Owner) + a default
     * warehouse + a SKU with an InventoryLevel of $onHand. Mirrors WarehouseDocumentsTest's setUp,
     * but callable twice per test to get two genuinely distinct tenants.
     *
     * @return array{0: User, 1: Tenant, 2: int, 3: int}
     */
    private function seedInventory(int $onHand): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop '.Str::random(6)]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $wh = Warehouse::defaultFor((int) $tenant->getKey());
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'sku_code' => 'SKU-'.Str::random(6), 'name' => 'Áo', 'cost_price' => 40000,
        ]);
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'sku_id' => $sku->getKey(), 'warehouse_id' => $wh->getKey(),
            'on_hand' => $onHand, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => $onHand,
        ]);

        return [$user, $tenant, (int) $wh->getKey(), (int) $sku->getKey()];
    }

    public function test_create_and_confirm_goods_issue_decreases_stock(): void
    {
        [$user, $tenant, $whId, $skuId] = $this->seedInventory(10);

        $create = $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/warehouse-docs/goods-issues', [
                'warehouse_id' => $whId, 'reason' => 'Hàng hỏng',
                'items' => [['sku_id' => $skuId, 'qty' => 4]],
            ])->assertCreated();
        $id = $create->json('data.id');
        $this->assertStringStartsWith('PXK-', $create->json('data.code'));

        $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/v1/warehouse-docs/goods-issues/{$id}/confirm")->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('sku_id', $skuId)->where('warehouse_id', $whId)->first();
        $this->assertSame(6, (int) $level->on_hand);
    }

    public function test_confirm_blocks_negative_stock_with_422(): void
    {
        [$user, $tenant, $whId, $skuId] = $this->seedInventory(3);
        $id = $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/warehouse-docs/goods-issues', ['warehouse_id' => $whId, 'items' => [['sku_id' => $skuId, 'qty' => 5]]])
            ->json('data.id');

        $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/v1/warehouse-docs/goods-issues/{$id}/confirm")
            ->assertStatus(422);

        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $skuId)->first();
        $this->assertSame(3, (int) $level->on_hand);
    }

    public function test_cannot_use_another_tenants_sku(): void
    {
        [$userA, $tenantA, $whA, $skuA] = $this->seedInventory(10);
        [$userB, $tenantB, $whB, $skuB] = $this->seedInventory(10); // distinct second tenant

        $this->actingAs($userA)->withHeader('X-Tenant-Id', (string) $tenantA->id)
            ->postJson('/api/v1/warehouse-docs/goods-issues', ['warehouse_id' => $whA, 'items' => [['sku_id' => $skuB, 'qty' => 1]]])
            ->assertStatus(422);

        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $skuB)->first();
        $this->assertSame(10, (int) $level->on_hand);
    }
}
