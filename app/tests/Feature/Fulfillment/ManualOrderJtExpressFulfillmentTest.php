<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Full flow đơn tự tạo dùng J&T Express (SPEC 0042). Khác GHN/GHTK/VTP:
 *   - Xác thực 2 tầng: apiAccount/privateKey cấp platform (config) + customerCode/password per-tenant.
 *   - Địa chỉ: chỉ selfAddress=1, không cần resolver ID (prov/area gửi thẳng tên).
 *   - Webhook: body {billCode, details:[...]}, KHÔNG có secret chuẩn — luôn ack + log cảnh báo.
 */
class ManualOrderJtExpressFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private CarrierAccount $jtAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        Config::set('integrations.jt.api_account', 'TEST-ACC');
        Config::set('integrations.jt.private_key', 'TEST-KEY');
        app(CarrierRegistry::class)->register('jt', JtExpressConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'JtShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'AO-01', 'name' => 'Áo thun', 'weight_grams' => 300,
        ]);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 50);

        $this->jtAccount = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'carrier' => 'jt',
            'name' => 'J&T — Kho Q10',
            'credentials' => ['customerCode' => '024E000014', 'password' => 'secret', 'webhook_secret' => 'WHSECRET'],
            'is_default' => true,
            'is_active' => true,
            'meta' => [
                'pay_type' => 'PP_CASH',
                'from_address' => [
                    'name' => 'CMBcore Shop', 'phone' => '0901234567', 'address' => '7/28 Thành Thái',
                    'province_name' => 'Hồ Chí Minh', 'ward_name' => 'Phường 14',
                ],
            ],
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function createManualOrderWithAddress(): int
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678', 'address' => '475A Điện Biên Phủ', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 2, 'unit_price' => 150000]],
            'shipping_fee' => 20000,
        ])->assertCreated()->json('data.id');

        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $order->shipping_address = array_merge((array) $order->shipping_address, [
            'province' => 'Hà Nội', 'ward' => 'Phường Hàng Trống', 'district' => null,
        ]);
        $order->save();

        return $orderId;
    }

    private function fakeJt(string $billCode = '802400616352'): void
    {
        Http::fake([
            '*/api/order/addOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => 'internal', 'billCode' => $billCode, 'sortLine' => '800-028A04-',
                'inquiryFee' => 15, 'codFee' => 0, 'insuranceFee' => 20000,
            ]]),
            '*/api/order/printOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => 'internal', 'billCode' => $billCode, 'base64EncodeContent' => base64_encode('%PDF-JT-FAKE'),
            ]]),
            '*/api/order/cancelOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['txlogisticId' => 'internal', 'billCode' => $billCode]]),
        ]);
    }

    public function test_prepare_creates_jt_order_and_stores_label(): void
    {
        $this->fakeJt('JT000001');
        $orderId = $this->createManualOrderWithAddress();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()])
            ->assertCreated()
            ->assertJsonPath('data.carrier', 'manual_jt')
            ->assertJsonPath('data.tracking_no', 'JT000001')
            ->assertJsonPath('data.status', 'created');

        $this->assertSame('processing', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'JT000001')->first();
        $this->assertNotNull($sh->label_path, 'Tem J&T phải được lưu tự động khi tạo vận đơn.');

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/api/order/addOrder')) {
                return false;
            }
            $biz = json_decode($req->data()['bizContent'], true);

            return ($biz['receiver']['prov'] ?? null) === 'Hà Nội'
                && ($biz['receiver']['area'] ?? null) === 'Phường Hàng Trống'
                && ! isset($biz['receiver']['city'])
                && ($biz['payType'] ?? null) === 'PP_CASH'
                && (int) ($biz['selfAddress'] ?? -1) === 1;
        });
    }

    public function test_cancel_calls_cancel_order_with_reason(): void
    {
        $this->fakeJt('JT000002');
        $orderId = $this->createManualOrderWithAddress();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()])
            ->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/shipments/{$shipId}/cancel")->assertOk();

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/api/order/cancelOrder')) {
                return false;
            }
            $biz = json_decode($req->data()['bizContent'], true);

            return ($biz['billCode'] ?? null) === 'JT000002' && ! empty($biz['reason']);
        });
    }

    public function test_webhook_syncs_status_and_always_acks(): void
    {
        $this->fakeJt('JT000003');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()])->assertCreated();

        // scanTypeCode 113 = Delivered. `?secret=` là quy ước riêng của app (nhúng vào URL tự cung cấp cho
        // J&T đăng ký) — không phải cơ chế J&T công bố, xem JtExpressConnector::parseWebhook.
        $this->postJson('/webhook/carriers/jt?secret=WHSECRET', [
            'billCode' => 'JT000003',
            'details' => [['scanTime' => '2026-07-17 10:00:00', 'scanTypeCode' => 113, 'desc' => 'Đã giao']],
        ])->assertOk()->assertJsonPath('data.acknowledged', true);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'JT000003')->first();
        $this->assertSame('delivered', $sh->status);
        $this->assertSame('delivered', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
    }

    public function test_webhook_rejects_mismatched_secret(): void
    {
        $this->fakeJt('JT000005');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()]);

        $this->postJson('/webhook/carriers/jt?secret=WRONG', [
            'billCode' => 'JT000005',
            'details' => [['scanTime' => '2026-07-17 10:00:00', 'scanTypeCode' => 113]],
        ])->assertStatus(401)->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');
    }

    public function test_webhook_idempotent_duplicates_no_op(): void
    {
        $this->fakeJt('JT000004');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()]);

        $body = ['billCode' => 'JT000004', 'details' => [['scanTime' => '2026-07-17 09:00:00', 'scanTypeCode' => 106, 'desc' => 'Đã lấy hàng']]];
        $this->postJson('/webhook/carriers/jt', $body)->assertOk();
        $this->postJson('/webhook/carriers/jt', $body)->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'JT000004')->first();
        $this->assertSame(1, $sh->events()->where('code', '106')->count(), 'Webhook trùng phải dedupe.');
    }

    public function test_webhook_without_billcode_acks_without_error(): void
    {
        $this->postJson('/webhook/carriers/jt', ['details' => [['scanTypeCode' => 106]]])
            ->assertOk()->assertJsonPath('data.acknowledged', true);
    }
}
