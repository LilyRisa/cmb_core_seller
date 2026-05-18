<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Services\ManualOrderService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualOrderWarehouseIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_warehouse_id_when_provided(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::create(['tenant_id' => $t->id, 'name' => 'Kho B', 'code' => 'B', 'is_default' => false]);

        $order = app(ManualOrderService::class)->create($t->id, null, [
            'recipient' => ['name' => 'X', 'phone' => '0901', 'address' => '123'],
            'items' => [['name' => 'A', 'quantity' => 1, 'unit_price' => 10000]],
            'warehouse_id' => $wh->id,
        ]);

        $this->assertSame($wh->id, $order->warehouse_id);
    }

    public function test_create_falls_back_to_default_warehouse(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::defaultFor($t->id);

        $order = app(ManualOrderService::class)->create($t->id, null, [
            'recipient' => ['name' => 'X', 'phone' => '0901', 'address' => '123'],
            'items' => [['name' => 'A', 'quantity' => 1, 'unit_price' => 10000]],
        ]);

        $this->assertSame($wh->id, $order->warehouse_id);
    }

    public function test_create_rejects_warehouse_id_from_other_tenant(): void
    {
        $t = Tenant::factory()->create();
        $other = Tenant::factory()->create();
        $wh = Warehouse::create(['tenant_id' => $other->id, 'name' => 'Foreign']);

        $this->expectException(ValidationException::class);
        app(ManualOrderService::class)->create($t->id, null, [
            'recipient' => ['name' => 'X', 'phone' => '0901', 'address' => '123'],
            'items' => [['name' => 'A', 'quantity' => 1, 'unit_price' => 10000]],
            'warehouse_id' => $wh->id,
        ]);
    }
}
