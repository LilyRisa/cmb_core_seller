<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
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
 * Full flow đơn tự tạo dùng GHTK (mirror GHN). GHTK khác GHN:
 *   - Địa chỉ dùng TÊN trực tiếp (không cần ID/Code).
 *   - COD = `pick_money`; tem trả PDF trực tiếp.
 *   - Webhook gửi status_id (số) + partner_id/label_id, KHÔNG có Token header → auth theo label_id.
 */
class ManualOrderGhtkFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private CarrierAccount $ghtkAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        app(CarrierRegistry::class)->register('ghtk', GhtkConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'GhtkShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'AO-01', 'name' => 'Áo thun', 'weight_grams' => 300,
        ]);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 50);

        $this->ghtkAccount = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'carrier' => 'ghtk',
            'name' => 'GHTK — Kho HN',
            'credentials' => ['token' => 'GHTK-TOKEN-1', 'client_source' => 'S12345'],
            'is_default' => true,
            'is_active' => true,
            'meta' => ['from_address' => [
                'name' => 'CMBcore Shop', 'phone' => '0901234567', 'address' => 'Số 1 Lê Lợi',
                'ward_name' => 'Phường Bến Nghé', 'district_name' => 'Quận 1', 'province_name' => 'TP. Hồ Chí Minh',
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

        // GHTK dùng TÊN tỉnh/huyện/xã — set trực tiếp lên shipping_address (form thật có AddressPicker).
        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $order->shipping_address = array_merge((array) $order->shipping_address, [
            'province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường Dịch Vọng',
        ]);
        $order->save();

        return $orderId;
    }

    private function fakeGhtk(string $label = 'S1.A1.99'): void
    {
        Http::fake([
            '*/services/shipment/order*' => Http::response([
                'success' => true, 'message' => '',
                'order' => [
                    'label' => $label, 'partner_id' => 'PARTNER-1', 'fee' => '25000', 'insurance_fee' => '0',
                    'estimated_pick_time' => 'Sáng 2026-06-04', 'estimated_deliver_time' => 'Chiều 2026-06-05', 'status_id' => 1,
                ],
            ]),
            '*/services/label/*' => Http::response('%PDF-GHTK-FAKE', 200, ['Content-Type' => 'application/pdf']),
        ]);
    }

    public function test_prepare_calls_ghtk_create_order_and_attaches_tracking(): void
    {
        $this->fakeGhtk('S1.A1.001');
        $orderId = $this->createManualOrderWithAddress();

        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghtkAccount->getKey()])
            ->assertCreated();

        $resp->assertJsonPath('data.carrier', 'manual_ghtk')
            ->assertJsonPath('data.tracking_no', 'S1.A1.001')
            ->assertJsonPath('data.status', 'created');

        $this->assertSame('processing', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);

        // Payload GHTK: pick_* từ from_address, recipient theo TÊN, pick_money = COD (= grand_total 320000).
        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/services/shipment/order')) {
                return false;
            }
            $b = json_decode($req->body(), true);
            $o = $b['order'] ?? [];

            return ($o['pick_name'] ?? null) === 'CMBcore Shop'
                && ($o['pick_province'] ?? null) === 'TP. Hồ Chí Minh'
                && ($o['province'] ?? null) === 'Hà Nội'
                && ($o['district'] ?? null) === 'Quận Cầu Giấy'
                && (int) ($o['pick_money'] ?? 0) === 320000
                && ($req->header('X-Client-Source')[0] ?? null) === 'S12345'
                && ! empty($b['products']);
        });
    }

    public function test_label_pdf_stored_directly_without_gotenberg(): void
    {
        $this->fakeGhtk('S1.A1.002');
        $orderId = $this->createManualOrderWithAddress();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghtkAccount->getKey()])
            ->json('data.id');

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->find($shipId);
        $this->assertNotNull($sh->label_path, 'Tem PDF GHTK phải được lưu trực tiếp.');
    }

    public function test_mark_packed_sets_awaiting_pickup_for_ghtk(): void
    {
        $this->fakeGhtk('S1.A1.003');
        $orderId = $this->createManualOrderWithAddress();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghtkAccount->getKey()])
            ->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shipId]])->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->find($shipId);
        $this->assertSame('awaiting_pickup', $sh->status);
        $this->assertSame('ready_to_ship', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
    }

    public function test_ghtk_webhook_syncs_status_without_token_header(): void
    {
        $this->fakeGhtk('S1.A1.004');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghtkAccount->getKey()])->assertCreated();

        // GHTK webhook: status_id=3 (đã lấy hàng) → shipment picked_up, order shipped. KHÔNG có Token header.
        $resp = $this->postJson('/webhook/carriers/ghtk', [
            'label_id' => 'S1.A1.004', 'partner_id' => 'PARTNER-1', 'status_id' => 3, 'action_time' => '2026-06-04T10:00:00+07:00',
        ], ['X-Client-Source' => 'S12345']);
        $resp->assertOk()->assertJsonPath('data.acknowledged', true);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'S1.A1.004')->first();
        $this->assertSame('picked_up', $sh->status);
        $this->assertSame('shipped', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
    }

    public function test_ghtk_webhook_idempotent_duplicates_no_op(): void
    {
        $this->fakeGhtk('S1.A1.005');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghtkAccount->getKey()]);

        $body = ['label_id' => 'S1.A1.005', 'partner_id' => 'P', 'status_id' => 2, 'action_time' => '2026-06-04T09:00:00+07:00'];
        $this->postJson('/webhook/carriers/ghtk', $body, ['X-Client-Source' => 'S12345'])->assertOk();
        $this->postJson('/webhook/carriers/ghtk', $body, ['X-Client-Source' => 'S12345'])->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'S1.A1.005')->first();
        $this->assertSame(1, $sh->events()->where('code', '2')->count(), 'Webhook trùng phải dedupe.');
    }

    public function test_ghtk_webhook_rejects_mismatched_client_source(): void
    {
        $this->fakeGhtk('S1.A1.006');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->ghtkAccount->getKey()]);

        $this->postJson('/webhook/carriers/ghtk', [
            'label_id' => 'S1.A1.006', 'status_id' => 3, 'action_time' => '2026-06-04T10:00:00+07:00',
        ], ['X-Client-Source' => 'WRONG-SOURCE'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');
    }
}
