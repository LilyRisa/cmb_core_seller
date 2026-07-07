<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Reconciliation (2026-07-07) — the carrier "delivery note" reuses `order.meta.print_note` instead of
 * the (now removed) `delivery_note` column. Shipping fee is INTERNAL only (already baked into the COD
 * pushed to the carrier) — `buildCreatePayload()` no longer emits `fee_payer`/`inspection` at all, and
 * the "chế độ xem hàng" (inspection) feature was removed entirely. `buildCreatePayload` is private, so
 * we invoke it via reflection (same technique used in ShipmentPayloadDeliveryOptionsTest for the
 * connector-level unit tests, but this method needs a real Order + tenant).
 */
class ShipmentBuildPayloadDeliveryOptionsTest extends TestCase
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

    public function test_delivery_note_sourced_from_print_note_meta(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-NOTE-1',
            'shipping_address' => [],
            'status' => 'pending',
            'meta' => ['print_note' => 'Gọi trước khi giao'],
        ]);

        $payload = $this->buildPayload($order);

        $this->assertSame('Gọi trước khi giao', $payload['delivery_note']);
    }

    public function test_delivery_note_blank_when_no_print_note(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-NOTE-2',
            'shipping_address' => [],
            'status' => 'pending',
            'meta' => [],
        ]);

        $payload = $this->buildPayload($order);

        $this->assertSame('', $payload['delivery_note']);
    }

    public function test_opts_override_delivery_note(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-NOTE-3',
            'shipping_address' => [],
            'status' => 'pending',
            'meta' => ['print_note' => 'ghi chú mặc định'],
        ]);

        $payload = $this->buildPayload($order, ['delivery_note' => 'ghi chú tuỳ chỉnh']);

        $this->assertSame('ghi chú tuỳ chỉnh', $payload['delivery_note']);
    }

    public function test_payload_never_includes_fee_payer_or_inspection(): void
    {
        // Phí ship là NỘI BỘ — đã gộp vào COD đẩy ĐVVC, app không map ai-trả-phí lên carrier. "Chế độ
        // xem hàng" đã bị bỏ hẳn. Cả 2 field KHÔNG được xuất hiện trong payload dù opts cố truyền lên.
        $tenant = Tenant::factory()->create();
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-NOFEE-1',
            'shipping_address' => [],
            'status' => 'pending',
            'meta' => ['free_shipping' => true],
        ]);

        $payload = $this->buildPayload($order, ['fee_payer' => 'shop', 'inspection' => 'view']);

        $this->assertArrayNotHasKey('fee_payer', $payload);
        $this->assertArrayNotHasKey('inspection', $payload);
    }

    public function test_failed_collect_amount_sourced_from_order_column(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-FAILCOL-1',
            'shipping_address' => [],
            'status' => 'pending',
            'failed_collect_amount' => 30000,
        ]);

        $payload = $this->buildPayload($order);

        $this->assertSame(30000, $payload['failed_collect_amount']);
    }

    public function test_opts_override_failed_collect_amount(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => 'M-FAILCOL-2',
            'shipping_address' => [],
            'status' => 'pending',
            'failed_collect_amount' => 30000,
        ]);

        $payload = $this->buildPayload($order, ['failed_collect_amount' => 15000]);

        $this->assertSame(15000, $payload['failed_collect_amount']);
    }
}
