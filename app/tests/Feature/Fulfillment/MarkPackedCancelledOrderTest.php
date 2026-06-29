<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * TDD – FIX F: markPacked() phải ném RuntimeException khi đơn đã bị huỷ.
 * Tránh chuyển vận đơn sang "chờ bàn giao" cho đơn đã huỷ.
 */
class MarkPackedCancelledOrderTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop cancelled order guard']);
        app(CurrentTenant::class)->set($this->tenant);
    }

    public function test_mark_packed_throws_for_cancelled_order(): void
    {
        // RED → GREEN after adding the cancelled-order guard in markPacked().
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual',
            'channel_account_id' => null,
            'external_order_id' => null,
            'order_number' => 'MAN-CANCELLED-1',
            'status' => StandardOrderStatus::Cancelled,
            'raw_status' => 'cancelled',
            'shipping_address' => ['fullName' => 'B', 'phone' => '0900000001'],
            'currency' => 'VND',
            'grand_total' => 200000,
            'item_total' => 200000,
            'is_cod' => false,
            'placed_at' => now()->subHour(),
            'source_updated_at' => now()->subHour(),
            'has_issue' => false,
            'tags' => [],
            'packages' => [],
        ]);

        // Vận đơn còn mở (STATUS_CREATED) — đây chính xác là tình huống cần chặn: shipment chưa
        // cancel nhưng đơn gốc đã bị sàn huỷ, quét đóng gói sẽ chuyển sang "chờ bàn giao" sai.
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'carrier' => 'manual',
            'tracking_no' => 'MAN-TN-CANCELLED',
            'status' => Shipment::STATUS_CREATED,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Đơn đã bị huỷ — không thể đóng gói.');

        app(ShipmentService::class)->markPacked($shipment);
    }
}
