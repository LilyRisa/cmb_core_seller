<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * SPEC 2026-06-26 — "Chuẩn bị hàng" bulk async: bulk-create validate rẻ đồng bộ rồi dispatch PrepareShipment;
 * job chạy nền (runAs tenant, idempotent). Single /ship GIỮ ĐỒNG BỘ (test ở FulfillmentTest).
 */
class PrepareShipmentAsyncTest extends TestCase
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

    /** @param array<string,mixed> $overrides */
    private function createOrder(array $overrides = []): int
    {
        return $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', array_merge([
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678', 'address' => 'Số 5', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 2, 'unit_price' => 150000]],
            'shipping_fee' => 20000,
        ], $overrides))->assertCreated()->json('data.id');
    }

    private function order(int $id): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->findOrFail($id);
    }

    // --- assertPreparable (validate rẻ, dùng chung) --------------------------

    public function test_assert_preparable_throws_for_cancelled_order(): void
    {
        $id = $this->createOrder();
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$id}/cancel")->assertOk();

        $this->expectException(\RuntimeException::class);
        app(ShipmentService::class)->assertPreparable($this->order($id));
    }

    public function test_assert_preparable_passes_for_pending_in_stock_order(): void
    {
        $id = $this->createOrder();
        app(ShipmentService::class)->assertPreparable($this->order($id));
        $this->assertTrue(true); // không throw
    }

    // --- bulk dispatch (async) ----------------------------------------------

    public function test_bulk_dispatches_jobs_and_separates_errors_and_already_prepared(): void
    {
        Queue::fake();
        $ok = $this->createOrder();
        $cancelled = $this->createOrder();
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$cancelled}/cancel")->assertOk();

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/shipments/bulk-create', ['order_ids' => [$ok, $cancelled, 999999]])
            ->assertOk();

        $res->assertJsonPath('data.queued', [$ok]);
        $errorIds = collect($res->json('data.errors'))->pluck('order_id')->all();
        $this->assertContains($cancelled, $errorIds);
        $this->assertContains(999999, $errorIds);

        Queue::assertPushed(PrepareShipment::class, 1);
        Queue::assertPushed(PrepareShipment::class, fn (PrepareShipment $j) => $j->orderId === $ok);
    }

    public function test_bulk_reports_already_prepared_for_order_with_open_shipment(): void
    {
        Queue::fake();
        $id = $this->createOrder();
        // Tạo sẵn vận đơn open ⇒ bulk coi là already_prepared, KHÔNG dispatch lại.
        Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $id, 'carrier' => 'manual',
            'tracking_no' => 'TN-EXIST', 'status' => Shipment::STATUS_CREATED,
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/shipments/bulk-create', ['order_ids' => [$id]])->assertOk();

        $res->assertJsonPath('data.already_prepared', [$id])->assertJsonPath('data.queued', []);
        Queue::assertNotPushed(PrepareShipment::class);
    }

    // --- job (chạy nền, không có request tenant) -----------------------------

    public function test_job_creates_shipment_without_request_tenant_and_is_idempotent(): void
    {
        $id = $this->createOrder();
        app(CurrentTenant::class)->clear(); // giả lập worker: không có tenant request-bound
        $svc = app(ShipmentService::class);
        $ct = app(CurrentTenant::class);

        (new PrepareShipment($id))->handle($svc, $ct);
        (new PrepareShipment($id))->handle($svc, $ct); // chạy lần 2 — idempotent

        $this->assertSame(1, Shipment::withoutGlobalScope(TenantScope::class)->where('order_id', $id)->count());
        $this->assertSame('processing', $this->order($id)->status->value);
    }
}
