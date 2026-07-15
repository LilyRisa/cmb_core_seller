<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Modules\Fulfillment\Jobs\SyncShipmentTracking;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * REGRESSION (prod 2026-07-15): `SyncShipmentTracking` (cron ~30') quét shipment CROSS-TENANT
 * (withoutGlobalScope) rồi gọi ShipmentService::syncTracking() thẳng, KHÔNG runAs(tenant) —
 * bên trong đó `$shipment->carrierAccount` là quan hệ tenant-scoped (BelongsToTenant), không có
 * tenant hiện tại thì TenantScope ép tenant_id=0 → quan hệ luôn null → GhnConnector::client()
 * ném "Tài khoản GHN chưa có token." dù carrier_account thật sự có token hợp lệ. syncTracking()
 * nuốt lỗi (catch + Log::warning) nên order kẹt "đang xử lý" vô thời hạn dù ĐVVC đã giao xong từ
 * lâu — 32 đơn thủ công GHN trên prod bị đúng lỗi này. Cùng lớp lỗi thiếu runAs đã gặp ở
 * FetchChannelLabel (xem FetchChannelLabelTenantContextTest). Fix: syncOne() runAs($shipment->tenant).
 */
class SyncShipmentTrackingTenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_sweep_updates_shipment_status_without_request_bound_tenant(): void
    {
        app(CarrierRegistry::class)->register('ghn', GhnConnector::class);

        $tenant = Tenant::create(['name' => 'GhnShop']);
        $account = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'carrier' => 'ghn', 'name' => 'GHN — Kho HN',
            'credentials' => ['token' => 'TEST-TOKEN-123', 'shop_id' => 9999],
            'is_default' => true, 'is_active' => true,
        ]);
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'source' => 'manual', 'channel_account_id' => null,
            'external_order_id' => 'M-1', 'order_number' => 'M-1',
            'status' => StandardOrderStatus::Processing, 'raw_status' => 'processing',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now()->subDays(5), 'source_updated_at' => now()->subDays(5),
            'tags' => [], 'carrier' => 'manual_ghn', 'packages' => [],
        ]);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'order_id' => $order->getKey(), 'carrier' => 'manual_ghn',
            'carrier_account_id' => $account->getKey(), 'tracking_no' => 'GH-ORDER-001',
            'status' => Shipment::STATUS_CREATED, 'cod_amount' => 0, 'raw' => [],
        ]);

        // GHN đã thực sự giao xong từ lâu — mô phỏng đúng triệu chứng thật trên prod.
        Http::fake([
            '*/shipping-order/detail' => Http::response([
                'code' => 200, 'message' => 'Success',
                'data' => ['status' => 'delivered', 'log' => [
                    ['status' => 'delivered', 'updated_date' => now()->subDays(2)->toIso8601String()],
                ]],
            ]),
        ]);

        // Mô phỏng WORKER chạy cron: KHÔNG có tenant request-bound.
        app(CurrentTenant::class)->clear();

        (new SyncShipmentTracking)->handle(app(ShipmentService::class), app(CurrentTenant::class));

        $fresh = Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey());
        $this->assertSame(
            Shipment::STATUS_DELIVERED,
            $fresh->status,
            'SyncShipmentTracking phải cập nhật được trạng thái GHN thật dù chạy trong worker không có tenant (runAs).'
        );

        $freshOrder = Order::withoutGlobalScope(TenantScope::class)->find($order->getKey());
        $this->assertSame(
            StandardOrderStatus::Delivered,
            $freshOrder->status,
            'Order phải chuyển sang delivered theo trạng thái ĐVVC thật, không kẹt processing.'
        );
    }
}
