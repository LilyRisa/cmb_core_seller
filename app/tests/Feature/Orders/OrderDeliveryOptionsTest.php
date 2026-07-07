<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeliveryOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_persists_delivery_options(): void
    {
        $tenant = Tenant::factory()->create();

        $o = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-1',
            'shipping_address' => [],
            'status' => 'pending',
            'failed_collect_amount' => 30000,
        ]);
        $o->refresh();
        $this->assertSame(30000, $o->failed_collect_amount);
    }
}
