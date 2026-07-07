<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Reconciliation (2026-07-07) — the "chế độ xem hàng" (inspection) feature was removed entirely
 * (never actually requested); `ShipmentService::inspectionToRequiredNote()` no longer exists.
 * `buildCreatePayload` needs a real Order + tenant (money/meta lookups) so this uses the same
 * reflection seam as `Tests\Feature\Fulfillment\ShipmentBuildPayloadDeliveryOptionsTest`.
 */
class ShipmentPayloadDeliveryOptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string,mixed> $opts */
    private function buildPayload(Order $order, array $opts = []): array
    {
        $service = app(ShipmentService::class);
        $method = new ReflectionMethod(ShipmentService::class, 'buildCreatePayload');
        $method->setAccessible(true);

        return $method->invoke($service, $order, (int) $order->tenant_id, null, 500, 0, $opts, ['meta' => []]);
    }

    public function test_build_create_payload_sources_delivery_note_and_failed_collect_amount(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-PAYLOAD-1',
            'shipping_address' => [],
            'status' => 'pending',
            'meta' => ['print_note' => 'Giao giờ hành chính'],
            'failed_collect_amount' => 25000,
        ]);

        $payload = $this->buildPayload($order);

        $this->assertSame('Giao giờ hành chính', $payload['delivery_note']);
        $this->assertSame(25000, $payload['failed_collect_amount']);
    }
}
