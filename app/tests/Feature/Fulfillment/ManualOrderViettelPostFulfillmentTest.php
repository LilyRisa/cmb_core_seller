<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostConnector;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Full flow đơn tự tạo dùng Viettel Post (SPEC 0034). Khác GHN/GHTK:
 *   - Xác thực: username/password → Login + ownerconnect (token cache).
 *   - Địa chỉ ID: resolver map TÊN người nhận → ID VTP (v3 đơn vị HC mới) trước khi tạo đơn.
 *   - In tem: printing-code → link digitalize → bytes.
 *   - Webhook: body DATA.ORDER_STATUS + TOKEN secret (mode tracking_lookup), KHÔNG có Token header.
 */
class ManualOrderViettelPostFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private CarrierAccount $vtpAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        Cache::flush();
        app(CarrierRegistry::class)->register('viettelpost', ViettelPostConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'VtpShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'AO-01', 'name' => 'Áo thun', 'weight_grams' => 300,
        ]);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 50);

        $this->vtpAccount = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'carrier' => 'viettelpost',
            'name' => 'VTP — Kho HN',
            'credentials' => ['username' => '0900000000', 'password' => 'secret', 'webhook_secret' => 'WHSECRET'],
            'default_service' => 'VCN',
            'is_default' => true,
            'is_active' => true,
            'meta' => ['from_address' => [
                'name' => 'CMBcore Shop', 'phone' => '0901234567', 'address' => 'Số 1 Lê Lợi',
                'province_id' => 1, 'ward_id' => 100, 'district_id' => 10,
                'province_name' => 'TP. Hồ Chí Minh', 'ward_name' => 'Phường Bến Nghé',
            ]],
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function createManualOrderWithAddress(): int
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678', 'address' => 'Số 5', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 2, 'unit_price' => 150000]],
            'shipping_fee' => 20000,
        ])->assertCreated()->json('data.id');

        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $order->shipping_address = array_merge((array) $order->shipping_address, [
            'province' => 'Hà Nội', 'ward' => 'Phường Dịch Vọng', 'district' => null,
        ]);
        $order->save();

        return $orderId;
    }

    private function fakeVtp(string $orderNumber = 'VTP123456'): void
    {
        Http::fake([
            '*/v2/user/Login' => Http::response(['status' => 200, 'error' => false, 'message' => 'OK', 'data' => ['token' => 'SHORT-TOKEN']]),
            '*/v2/user/ownerconnect' => Http::response(['status' => 200, 'error' => false, 'message' => 'OK', 'data' => ['token' => 'LONG-TOKEN']]),
            // Danh mục v3 (đơn vị HC mới) cho resolver người nhận.
            '*/v3/categories/listProvinceNew' => Http::response(['status' => 200, 'error' => false, 'data' => [
                ['PROVINCE_ID' => 5, 'PROVINCE_NAME' => 'Hà Nội', 'PROVINCE_CODE' => 'ADM1-01'],
            ]]),
            '*/v3/categories/listWardsNew*' => Http::response(['status' => 200, 'error' => false, 'data' => [
                ['WARDS_ID' => 9999, 'WARDS_NAME' => 'Phường Dịch Vọng', 'DISTRICT_ID' => 563],
            ]]),
            '*/v2/order/createOrder' => Http::response(['status' => 200, 'error' => false, 'message' => 'OK', 'data' => [
                'ORDER_NUMBER' => $orderNumber, 'MONEY_TOTAL' => 25000, 'EXCHANGE_WEIGHT' => 600,
            ]]),
            '*/v2/order/printing-code' => Http::response(['status' => 200, 'error' => false, 'message' => 'PRINTCODE-ABC=']),
            '*/DigitalizePrint/report.do*' => Http::response('%PDF-VTP-FAKE', 200, ['Content-Type' => 'application/pdf']),
            '*/v2/order/UpdateOrder' => Http::response(['status' => 200, 'error' => false, 'message' => 'Hủy đơn hàng thành công', 'data' => null]),
        ]);
    }

    public function test_prepare_resolves_address_and_creates_vtp_order(): void
    {
        $this->fakeVtp('VTP000001');
        $orderId = $this->createManualOrderWithAddress();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->vtpAccount->getKey()])
            ->assertCreated()
            ->assertJsonPath('data.carrier', 'manual_viettelpost')
            ->assertJsonPath('data.tracking_no', 'VTP000001')
            ->assertJsonPath('data.status', 'created');

        $this->assertSame('processing', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);

        // Payload createOrder: RECEIVER ID resolve từ tên (v3), SENDER ID từ from_address, COD ⇒ ORDER_PAYMENT=3.
        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/v2/order/createOrder')) {
                return false;
            }
            $b = json_decode($req->body(), true);

            return (int) ($b['RECEIVER_PROVINCE'] ?? 0) === 5
                && (int) ($b['RECEIVER_WARD'] ?? 0) === 9999
                && (int) ($b['SENDER_PROVINCE'] ?? 0) === 1
                && (int) ($b['SENDER_WARD'] ?? 0) === 100
                && ($b['ORDER_SERVICE'] ?? null) === 'VCN'
                && (int) ($b['ORDER_PAYMENT'] ?? 0) === 3
                && (int) ($b['MONEY_COLLECTION'] ?? 0) === 320000
                && ($req->header('Token')[0] ?? null) === 'LONG-TOKEN';
        });
    }

    public function test_label_pdf_stored_from_printing_code_link(): void
    {
        $this->fakeVtp('VTP000002');
        $orderId = $this->createManualOrderWithAddress();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->vtpAccount->getKey()])
            ->json('data.id');

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->find($shipId);
        $this->assertNotNull($sh->label_path, 'Tem PDF VTP phải được lưu từ link in.');
        Http::assertSent(fn ($req) => str_contains($req->url(), '/v2/order/printing-code'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/DigitalizePrint/report.do'));
    }

    public function test_mark_packed_sets_awaiting_pickup(): void
    {
        $this->fakeVtp('VTP000003');
        $orderId = $this->createManualOrderWithAddress();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->vtpAccount->getKey()])
            ->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shipId]])->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->find($shipId);
        $this->assertSame('awaiting_pickup', $sh->status);
        $this->assertSame('ready_to_ship', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
    }

    public function test_cancel_calls_update_order_type_4(): void
    {
        $this->fakeVtp('VTP000004');
        $orderId = $this->createManualOrderWithAddress();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->vtpAccount->getKey()])
            ->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/shipments/{$shipId}/cancel")->assertOk();

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/v2/order/UpdateOrder')) {
                return false;
            }
            $b = json_decode($req->body(), true);

            return (int) ($b['TYPE'] ?? 0) === 4 && ($b['ORDER_NUMBER'] ?? null) === 'VTP000004';
        });
    }

    public function test_webhook_syncs_status_with_matching_secret(): void
    {
        $this->fakeVtp('VTP000005');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->vtpAccount->getKey()])->assertCreated();

        // ORDER_STATUS=200 (lấy hàng thành công) → shipment picked_up, order shipped.
        $this->postJson('/webhook/carriers/viettelpost', [
            'DATA' => ['ORDER_NUMBER' => 'VTP000005', 'ORDER_STATUS' => 200, 'STATUS_NAME' => 'Lấy hàng thành công', 'ORDER_STATUSDATE' => '04/06/2026 10:00:00'],
            'TOKEN' => 'WHSECRET',
        ])->assertOk()->assertJsonPath('data.acknowledged', true);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'VTP000005')->first();
        $this->assertSame('picked_up', $sh->status);
        $this->assertSame('shipped', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
    }

    public function test_webhook_rejects_mismatched_secret(): void
    {
        $this->fakeVtp('VTP000006');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->vtpAccount->getKey()]);

        $this->postJson('/webhook/carriers/viettelpost', [
            'DATA' => ['ORDER_NUMBER' => 'VTP000006', 'ORDER_STATUS' => 200, 'ORDER_STATUSDATE' => '04/06/2026 10:00:00'],
            'TOKEN' => 'WRONG-SECRET',
        ])->assertStatus(401)->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');
    }

    public function test_webhook_idempotent_duplicates_no_op(): void
    {
        $this->fakeVtp('VTP000007');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->vtpAccount->getKey()]);

        $body = [
            'DATA' => ['ORDER_NUMBER' => 'VTP000007', 'ORDER_STATUS' => 300, 'ORDER_STATUSDATE' => '04/06/2026 09:00:00'],
            'TOKEN' => 'WHSECRET',
        ];
        $this->postJson('/webhook/carriers/viettelpost', $body)->assertOk();
        $this->postJson('/webhook/carriers/viettelpost', $body)->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'VTP000007')->first();
        $this->assertSame(1, $sh->events()->where('code', '300')->count(), 'Webhook trùng phải dedupe.');
    }
}
