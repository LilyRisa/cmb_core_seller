<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 3 — POST/PATCH /api/v1/orders phải nhận + lưu tuỳ chọn giao hàng (failed_collect_amount) vào
 * order thủ công. Cột + $fillable đã có từ Task 1-2.
 *
 * Reconciliation (2026-07-07): delivery_note & delivery_fee_payer KHÔNG còn là cột riêng — "ghi chú
 * giao hàng" reuse meta.print_note (xem ShipmentBuildPayloadDeliveryOptionsTest cho phần sourcing ở
 * ShipmentService). Phí ship là NỘI BỘ (gộp vào COD) — app không map ai-trả-phí lên carrier. "Chế độ
 * xem hàng" (delivery_inspection) đã bị bỏ hẳn — không phải cột/field API nữa.
 */
class ManualOrderDeliveryOptionsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_store_persists_delivery_options(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000'],
            'items' => [['name' => 'Bút', 'quantity' => 1, 'unit_price' => 10000]],
            'failed_collect_amount' => 30000,
        ]);

        $res->assertCreated();
        $this->assertDatabaseHas('orders', [
            'id' => $res->json('data.id'),
            'failed_collect_amount' => 30000,
        ]);
    }

    public function test_update_persists_delivery_options(): void
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678'],
            'items' => [['name' => 'Bút', 'quantity' => 1, 'unit_price' => 10000]],
        ])->assertCreated()->json('data.id');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/orders/{$orderId}", [
            'failed_collect_amount' => 15000,
        ]);

        $res->assertOk();
        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $this->assertSame(15000, (int) $order->failed_collect_amount);
    }
}
