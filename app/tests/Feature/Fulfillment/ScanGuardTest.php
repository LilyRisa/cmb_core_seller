<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * TDD — scan guard: quét đóng gói (scan-pack) chỉ được khi đơn Processing + không lỗi + đã có tem sàn.
 *
 * Covers SPEC 2026-06-29 scan-guard-processing-label-design.md §A.
 *
 * NOTE: Tất cả test dùng scan-pack HTTP endpoint qua findByScanCode (chỉ trả shipment có status ∈ open()).
 * `shipment_cancelled` không thể kiểm tra qua endpoint này vì STATUS_CANCELLED ∉ OPEN_STATUSES;
 * cancelled shipments luôn dẫn đến 404, không phải 409. Xem MarkPackedCancelledOrderTest cho service-level.
 */
class ScanGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop scan guard']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    /** Tạo đơn hàng trực tiếp qua DB (không qua API) để kiểm soát trạng thái. */
    private function makeOrder(array $overrides = []): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual',
            'channel_account_id' => null,
            'external_order_id' => null,
            'order_number' => 'SG-'.uniqid(),
            'status' => StandardOrderStatus::Processing,
            'raw_status' => 'processing',
            'shipping_address' => ['fullName' => 'Test', 'phone' => '0900000000'],
            'currency' => 'VND',
            'grand_total' => 100000,
            'item_total' => 100000,
            'is_cod' => false,
            'placed_at' => now()->subHour(),
            'source_updated_at' => now()->subHour(),
            'has_issue' => false,
            'issue_reason' => null,
            'tags' => [],
            'packages' => [],
        ], $overrides));
    }

    /** Tạo vận đơn trực tiếp qua DB với label_path đã có (mặc định cho phần lớn test). */
    private function makeShipment(Order $order, array $overrides = []): Shipment
    {
        return Shipment::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'carrier' => 'manual',
            'tracking_no' => 'SG-TN-'.uniqid(),
            'status' => Shipment::STATUS_CREATED,
            'label_path' => 'labels/test-label.pdf',
        ], $overrides));
    }

    // ── Happy path ──────────────────────────────────────────────────────────

    /**
     * Đủ điều kiện: Processing + no issue + label_path → 200 đóng gói thành công,
     * đơn chuyển sang ReadyToShip.
     */
    public function test_scan_succeeds_for_processing_no_issue_with_label(): void
    {
        $order = $this->makeOrder();
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertOk()
            ->assertJsonPath('data.action', 'pack')
            ->assertJsonPath('data.shipment.status', 'packed')
            ->assertJsonPath('data.order.status', 'ready_to_ship');

        $this->assertNotSame(
            StandardOrderStatus::Processing,
            Order::withoutGlobalScope(TenantScope::class)->find($order->getKey())->status,
            'Đơn phải chuyển sang ReadyToShip sau khi quét thành công.'
        );
    }

    // ── order_not_processing ─────────────────────────────────────────────────

    /** Đơn Pending → 409 order_not_processing. */
    public function test_scan_blocked_when_order_is_pending(): void
    {
        $order = $this->makeOrder(['status' => StandardOrderStatus::Pending, 'raw_status' => 'pending']);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'order_not_processing');
        $this->assertNotEmpty($resp->json('message'), 'Response phải kèm message.');
    }

    /** Đơn ReadyToShip (đã chuẩn bị xong, chưa đóng gói shipment) → 409 order_not_processing. */
    public function test_scan_blocked_when_order_is_ready_to_ship(): void
    {
        $order = $this->makeOrder(['status' => StandardOrderStatus::ReadyToShip, 'raw_status' => 'ready_to_ship']);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'order_not_processing');
    }

    // ── order_has_issue ───────────────────────────────────────────────────────

    /** Đơn có lỗi kèm lý do → 409 order_has_issue, message chứa lý do. */
    public function test_scan_blocked_when_order_has_issue_with_reason(): void
    {
        $order = $this->makeOrder(['has_issue' => true, 'issue_reason' => 'Thiếu hàng trong kho']);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'order_has_issue');
        $this->assertStringContainsString('Thiếu hàng trong kho', (string) $resp->json('message'));
    }

    /** Đơn có lỗi nhưng không có lý do → 409 order_has_issue, message chung. */
    public function test_scan_blocked_when_order_has_issue_without_reason(): void
    {
        $order = $this->makeOrder(['has_issue' => true, 'issue_reason' => null]);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'order_has_issue');
        $this->assertNotEmpty($resp->json('message'), 'Response phải kèm message dù không có issue_reason.');
    }

    // ── label_missing ─────────────────────────────────────────────────────────

    /** Vận đơn chưa có tem (label_path = null) → 409 label_missing. */
    public function test_scan_blocked_when_label_path_is_null(): void
    {
        $order = $this->makeOrder();
        $shipment = $this->makeShipment($order, ['carrier' => 'ghn', 'label_path' => null]);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'label_missing');
    }

    /** Vận đơn với label_path rỗng (blank string) → 409 label_missing. */
    public function test_scan_blocked_when_label_path_is_empty_string(): void
    {
        $order = $this->makeOrder();
        $shipment = $this->makeShipment($order, ['carrier' => 'ghn', 'label_path' => '']);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'label_missing');
    }

    /**
     * Đơn tự ship "manual" KHÔNG có tem (label_path null) VẪN quét được — người bán tự dán tem.
     * (label_missing chỉ áp dụng cho carrier ≠ manual; xem luồng manual self-ship trong FulfillmentTest.)
     */
    public function test_scan_succeeds_for_manual_carrier_without_label(): void
    {
        $order = $this->makeOrder();
        $shipment = $this->makeShipment($order, ['carrier' => 'manual', 'label_path' => null]);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertOk()
            ->assertJsonPath('data.action', 'pack')
            ->assertJsonPath('data.shipment.status', 'packed');
    }

    /** Đơn Shipped (đang vận chuyển) → 409 order_not_processing (spec liệt kê Shipped cùng Pending/ReadyToShip). */
    public function test_scan_blocked_when_order_is_shipped(): void
    {
        $order = $this->makeOrder(['status' => StandardOrderStatus::Shipped, 'raw_status' => 'shipped']);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'order_not_processing');
    }

    // ── already_packed (quét trùng — ưu tiên hơn order_not_processing) ───────

    /**
     * Đơn đã đóng gói (shipment = packed, đơn đã = ReadyToShip): quét lại phải trả
     * already_packed, KHÔNG phải order_not_processing — dù đơn không còn Processing nữa.
     * (Xem spec: check already_packed đứng trước check order_not_processing.)
     */
    public function test_scan_returns_already_packed_not_order_not_processing_on_rescan(): void
    {
        // Sau khi markPacked lần đầu: đơn → ReadyToShip, shipment → packed.
        $order = $this->makeOrder(['status' => StandardOrderStatus::ReadyToShip, 'raw_status' => 'ready_to_ship']);
        $shipment = $this->makeShipment($order, ['status' => Shipment::STATUS_PACKED]);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'already_packed');
    }

    // ── Cancelled ────────────────────────────────────────────────────────────

    /**
     * Đơn hàng đã bị huỷ (shipment vẫn open/created) → 409 order_cancelled.
     * (shipment_cancelled không thể test qua HTTP endpoint vì findByScanCode lọc
     * STATUS_CANCELLED qua open() scope — xem MarkPackedCancelledOrderTest cho service.)
     */
    public function test_scan_blocked_when_order_is_cancelled(): void
    {
        $order = $this->makeOrder(['status' => StandardOrderStatus::Cancelled, 'raw_status' => 'cancelled']);
        $shipment = $this->makeShipment($order);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/scan-pack', ['code' => $shipment->tracking_no]);

        $resp->assertStatus(409)
            ->assertJsonPath('code', 'order_cancelled');
    }
}
