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

    /** @param array<string,mixed> $opts @param array<string,mixed> $accountArr */
    private function buildPayload(Order $order, array $opts = [], array $accountArr = ['meta' => []]): array
    {
        $service = app(ShipmentService::class);
        $method = new ReflectionMethod(ShipmentService::class, 'buildCreatePayload');
        $method->setAccessible(true);

        return $method->invoke($service, $order, (int) $order->tenant_id, null, 500, 0, $opts, $accountArr);
    }

    private function makeOrder(string $num, array $over = []): Order
    {
        $tenant = Tenant::factory()->create();

        return Order::create(array_merge([
            'tenant_id' => $tenant->id,
            'source' => 'manual',
            'order_number' => $num,
            'shipping_address' => [],
            'status' => 'pending',
        ], $over));
    }

    public function test_build_create_payload_sources_delivery_note_and_failed_collect_amount(): void
    {
        $order = $this->makeOrder('M-PAYLOAD-1', [
            'meta' => ['print_note' => 'Giao giờ hành chính'],
            'failed_collect_amount' => 25000,
        ]);

        $payload = $this->buildPayload($order);

        $this->assertSame('Giao giờ hành chính', $payload['delivery_note']);
        $this->assertSame(25000, $payload['failed_collect_amount']);
    }

    public function test_default_required_note_is_show_without_try(): void
    {
        // Không cài đặt gì (opts/order.meta/account defaults đều trống) ⇒ mặc định an toàn CHOXEMHANGKHONGTHU
        // (bỏ ép "cho thử hàng" cũ). Kích thước gói fallback cứng 15/15/10.
        $payload = $this->buildPayload($this->makeOrder('M-DEF-1'));

        $this->assertSame('CHOXEMHANGKHONGTHU', $payload['required_note']);
        $this->assertTrue($payload['allow_inspection']);
        $this->assertSame('light', $payload['goods_type']);
        $this->assertNull($payload['pick_station_id']);
        $this->assertSame(15, $payload['parcel']['length_cm']);
        $this->assertSame(10, $payload['parcel']['height_cm']);
    }

    public function test_uses_account_defaults(): void
    {
        $accountArr = ['meta' => ['defaults' => [
            'package' => ['length_cm' => 30, 'width_cm' => 20, 'height_cm' => 12],
            'goods_type' => 'heavy',
            'required_note' => 'KHONGCHOXEMHANG',
            'pickup' => ['at_station' => true, 'station_id' => 987],
        ]]];

        $payload = $this->buildPayload($this->makeOrder('M-ACC-1'), [], $accountArr);

        $this->assertSame('KHONGCHOXEMHANG', $payload['required_note']);
        $this->assertFalse($payload['allow_inspection']);
        $this->assertSame('heavy', $payload['goods_type']);
        $this->assertSame(987, $payload['pick_station_id']);
        $this->assertSame(30, $payload['parcel']['length_cm']);
        $this->assertSame(12, $payload['parcel']['height_cm']);
    }

    public function test_order_meta_and_opts_override_account_defaults(): void
    {
        $accountArr = ['meta' => ['defaults' => ['required_note' => 'KHONGCHOXEMHANG']]];

        // order.meta.required_note thắng account default.
        $order = $this->makeOrder('M-OVR-1', ['meta' => ['required_note' => 'CHOTHUHANG']]);
        $this->assertSame('CHOTHUHANG', $this->buildPayload($order, [], $accountArr)['required_note']);

        // opts (đẩy tay) thắng tất cả.
        $this->assertSame('CHOXEMHANGKHONGTHU', $this->buildPayload($order, ['required_note' => 'CHOXEMHANGKHONGTHU'], $accountArr)['required_note']);
    }

    public function test_backward_compat_allow_inspection_bool(): void
    {
        // Đơn CŨ chỉ lưu cờ bool allow_inspection ⇒ map: true→CHOTHUHANG, thắng account default.
        $order = $this->makeOrder('M-BC-1', ['meta' => ['allow_inspection' => true]]);
        $accountArr = ['meta' => ['defaults' => ['required_note' => 'KHONGCHOXEMHANG']]];
        $this->assertSame('CHOTHUHANG', $this->buildPayload($order, [], $accountArr)['required_note']);
    }
}
