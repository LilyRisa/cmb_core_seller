<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BulkPackResultsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'SKU-1', 'name' => 'Áo', 'weight_grams' => 300]);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 50);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function shippedOrder(string $tn): int
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'A', 'phone' => '0912345678', 'address' => 'X', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo', 'quantity' => 1, 'unit_price' => 100000]],
            'shipping_fee' => 10000,
        ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/ship", ['tracking_no' => $tn])->assertCreated();

        return $orderId;
    }

    private function shipmentFor(int $orderId): Shipment
    {
        return Shipment::withoutGlobalScope(TenantScope::class)->where('order_id', $orderId)->firstOrFail();
    }

    public function test_bulk_pack_returns_per_order_results(): void
    {
        $s1 = $this->shipmentFor($this->shippedOrder('TN-1'));
        $s2 = $this->shipmentFor($this->shippedOrder('TN-2'));
        // pre-pack s2 so it becomes a skipped no-op on the second call
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$s2->getKey()]])->assertOk();

        // include a non-existent shipment id (999999) → must still get a result row (skipped), not be dropped
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$s1->getKey(), $s2->getKey(), 999999]])->assertOk();

        $results = collect($res->json('data.results'));
        $this->assertSame('ok', $results->firstWhere('id', $s1->getKey())['status']);
        $this->assertSame('skipped', $results->firstWhere('id', $s2->getKey())['status']);
        $this->assertSame('skipped', $results->firstWhere('id', 999999)['status']);
        $this->assertSame('Không tìm thấy vận đơn.', $results->firstWhere('id', 999999)['reason']);
        $this->assertSame(1, $res->json('data.packed'));
    }

    public function test_bulk_handover_returns_per_order_results(): void
    {
        $s1 = $this->shipmentFor($this->shippedOrder('TN-3'));
        // first handover ok, second is idempotent no-op → skipped
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/handover', ['shipment_ids' => [$s1->getKey()]])->assertOk()->assertJsonPath('data.handed_over', 1);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/handover', ['shipment_ids' => [$s1->getKey()]])->assertOk();
        $this->assertSame('skipped', collect($res->json('data.results'))->firstWhere('id', $s1->getKey())['status']);
        $this->assertSame(0, $res->json('data.handed_over'));
    }
}
