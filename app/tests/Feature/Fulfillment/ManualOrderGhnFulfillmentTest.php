<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0021 — full flow đơn tự tạo dùng GHN:
 *   1. Tạo manual order → pending.
 *   2. "Chuẩn bị hàng" → GHN createOrder được gọi NGAY → tracking về → shipment.status=created,
 *      order=processing. Mã vận đơn có sẵn trên phiếu in.
 *   3. "Sẵn sàng bàn giao" (markPacked) → KHÔNG gọi GHN API mới → shipment.status=awaiting_pickup
 *      (cap `awaiting_pickup_flow`), order=ready_to_ship.
 *   4. GHN webhook /webhook/carriers/ghn `picked` → shipment.status=picked_up, order=shipped.
 *   5. GHN webhook `delivered` → order=delivered.
 */
class ManualOrderGhnFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private CarrierAccount $ghnAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        // Đảm bảo GHN connector được đăng ký trong test (env mặc định không bật).
        app(CarrierRegistry::class)->register('ghn', GhnConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'GhnShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'AO-01', 'name' => 'Áo thun', 'weight_grams' => 300,
        ]);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 50);

        $this->ghnAccount = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'carrier' => 'ghn',
            'name' => 'GHN — Kho HN',
            'credentials' => ['token' => 'TEST-TOKEN-123', 'shop_id' => 9999],
            'is_default' => true,
            'is_active' => true,
            'meta' => ['from_address' => [
                'name' => 'CMBcore Shop', 'phone' => '0901234567',
                'address' => 'Số 1 Lê Lợi', 'ward_name' => 'P. Bến Nghé', 'district_name' => 'Quận 1',
                'province_name' => 'TP HCM', 'district_id' => 1442, 'ward_code' => '20308',
            ]],
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function createManualOrder(): int
    {
        return $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            // Khớp shape của FulfillmentTest::createOrder (cần đủ province để CustomerLinkingService không crash).
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678', 'address' => 'Số 5', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 2, 'unit_price' => 150000]],
            'shipping_fee' => 20000,
        ])->assertCreated()->json('data.id');
    }

    private function setOrderGhnAddress(int $orderId): void
    {
        // Set district_id + ward_code trên order.shipping_address vì manual order chưa có
        // address resolver. Trong production: form tạo đơn sẽ có Select theo GHN master-data.
        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $order->shipping_address = array_merge((array) $order->shipping_address, [
            'district_id' => 1493, 'ward_code' => '40113',
        ]);
        $order->save();
    }

    public function test_prepare_calls_ghn_create_order_and_attaches_tracking(): void
    {
        Http::fake([
            '*/shipping-order/create' => Http::response([
                'code' => 200, 'message' => 'Success',
                'data' => ['order_code' => 'GH-ORDER-001', 'total_fee' => 30000],
            ]),
            '*/a5/gen-token' => Http::response(['code' => 200, 'data' => ['token' => 'PRINT-TOKEN']]),
            '*/a6/public-api/printA6*' => Http::response('%PDF-FAKE', 200),
        ]);

        $orderId = $this->createManualOrder();
        $this->setOrderGhnAddress($orderId);

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghnAccount->getKey()])
            ->assertCreated();

        // SPEC 0021 — đơn manual + GHN ⇒ carrier lưu prefix 'manual_ghn' để phân biệt với đơn sàn qua GHN.
        $resp->assertJsonPath('data.carrier', 'manual_ghn')
            ->assertJsonPath('data.tracking_no', 'GH-ORDER-001')
            ->assertJsonPath('data.status', 'created');

        $this->assertSame('processing', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);

        // GHN API đã được gọi với from_address từ carrier_account.meta.
        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/shipping-order/create')) {
                return false;
            }
            $b = json_decode($req->body(), true);

            return ($b['from_name'] ?? null) === 'CMBcore Shop'
                && (int) ($b['from_district_id'] ?? 0) === 0  // GHN auto-resolves from saved pickup address
                && (int) ($b['to_district_id'] ?? 0) === 1493
                && ($b['to_ward_code'] ?? null) === '40113'
                && ($b['client_order_code'] ?? null) !== null;
        });
    }

    public function test_mark_packed_sets_awaiting_pickup_for_ghn_carrier(): void
    {
        Http::fake([
            '*/shipping-order/create' => Http::response(['code' => 200, 'data' => ['order_code' => 'GH-002', 'total_fee' => 0]]),
            '*/a5/gen-token' => Http::response(['code' => 200, 'data' => ['token' => 'T']]),
            '*/a6/public-api/printA6*' => Http::response('%PDF', 200),
        ]);
        $orderId = $this->createManualOrder();
        $this->setOrderGhnAddress($orderId);
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghnAccount->getKey()])
            ->json('data.id');

        // "Sẵn sàng bàn giao" — KHÔNG gọi thêm API GHN, chỉ flip state.
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shipId]])
            ->assertOk();
        $resp->assertJsonPath('data.packed', 1);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->find($shipId);
        $this->assertSame('awaiting_pickup', $sh->status);
        $this->assertNotNull($sh->packed_at);

        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $this->assertSame('ready_to_ship', $order->status->value);
    }

    public function test_manual_carrier_still_uses_packed_status_not_awaiting_pickup(): void
    {
        // Đối chiếu: manual carrier KHÔNG có cap `awaiting_pickup_flow` ⇒ giữ behavior cũ.
        $manualAcc = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'carrier' => 'manual', 'name' => 'Tự ship',
            'is_default' => false, 'is_active' => true,
        ]);
        $orderId = $this->createManualOrder();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $manualAcc->getKey(), 'tracking_no' => 'MAN-1'])
            ->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shipId]])->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->find($shipId);
        $this->assertSame('packed', $sh->status, 'Manual carrier vẫn dùng `packed`, không `awaiting_pickup`.');
    }

    public function test_ghn_webhook_picked_syncs_shipment_and_order(): void
    {
        Http::fake([
            '*/shipping-order/create' => Http::response(['code' => 200, 'data' => ['order_code' => 'GH-WH-1', 'total_fee' => 0]]),
            '*/a5/gen-token' => Http::response(['code' => 200, 'data' => ['token' => 'T']]),
            '*/a6/public-api/printA6*' => Http::response('%PDF', 200),
        ]);
        $orderId = $this->createManualOrder();
        $this->setOrderGhnAddress($orderId);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghnAccount->getKey()])->assertCreated();

        // Webhook GHN: status `picked` → shipment.status=picked_up, order=shipped.
        $resp = $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GH-WH-1', 'Status' => 'picked', 'Time' => '2026-05-16T10:00:00+07:00',
        ], ['Token' => 'TEST-TOKEN-123']);
        $resp->assertOk()->assertJsonPath('data.acknowledged', true);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'GH-WH-1')->first();
        $this->assertSame('picked_up', $sh->status);
        $this->assertNotNull($sh->picked_up_at);

        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $this->assertSame('shipped', $order->status->value);
    }

    public function test_ghn_webhook_rejects_invalid_token(): void
    {
        $this->postJson('/webhook/carriers/ghn', [
            'OrderCode' => 'GH-ANY', 'Status' => 'picked',
        ], ['Token' => 'WRONG-TOKEN'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');
    }

    public function test_ghn_webhook_idempotent_duplicates_no_op(): void
    {
        Http::fake([
            '*/shipping-order/create' => Http::response(['code' => 200, 'data' => ['order_code' => 'GH-IDEM', 'total_fee' => 0]]),
            '*/a5/gen-token' => Http::response(['code' => 200, 'data' => ['token' => 'T']]),
            '*/a6/public-api/printA6*' => Http::response('%PDF', 200),
        ]);
        $orderId = $this->createManualOrder();
        $this->setOrderGhnAddress($orderId);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghnAccount->getKey()]);

        $body = ['OrderCode' => 'GH-IDEM', 'Status' => 'picking', 'Time' => '2026-05-16T09:00:00+07:00'];
        $this->postJson('/webhook/carriers/ghn', $body, ['Token' => 'TEST-TOKEN-123'])->assertOk();
        $this->postJson('/webhook/carriers/ghn', $body, ['Token' => 'TEST-TOKEN-123'])->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'GH-IDEM')->first();
        $eventCount = $sh->events()->where('code', 'picking')->count();
        $this->assertSame(1, $eventCount, 'Webhook trùng phải dedupe qua (shipment_id, code, occurred_at).');
        $this->assertSame('awaiting_pickup', $sh->status);
    }

    public function test_ghn_create_shipment_fails_fast_when_address_missing(): void
    {
        $orderId = $this->createManualOrder();
        // KHÔNG set district_id/ward_code ⇒ validateShipmentPayload throw RuntimeException.
        // ShipmentController bắt RuntimeException → ValidationException ⇒ 422 với field `order`.

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghnAccount->getKey()]);
        $resp->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
        // Đọc detail qua json() để tránh việc message UTF-8 bị unicode-escape trong raw body.
        $orderErrors = $resp->json('error.details.order') ?? [];
        $msg = $orderErrors[0] ?? '';
        $this->assertStringContainsString('mã quận của GHN', $msg);
    }
}
