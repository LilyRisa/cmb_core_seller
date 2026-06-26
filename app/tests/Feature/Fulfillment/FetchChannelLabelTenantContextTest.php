<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Jobs\FetchChannelLabel;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures as ShopeeFx;
use Tests\TestCase;

/**
 * REGRESSION (SPEC 2026-06-26): FetchChannelLabel chạy trong WORKER không có request-bound tenant.
 * Trước fix, các query tenant-scoped trong ShipmentService (ChannelAccount::find → TenantScope ép tenant_id=0
 * → null) khiến channelLabelBeforeTracking() = false + fetchAndStoreChannelLabel early-return ⇒ job no-op âm
 * thầm, tem KHÔNG bao giờ được kéo. Fix: job phải runAs($shop). Test này dựng tem sàn SẴN SÀNG rồi chạy job
 * với CurrentTenant ĐÃ CLEAR (mô phỏng worker) — phải kéo + lưu được tem.
 */
class FetchChannelLabelTenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_fetches_label_without_request_bound_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        app(ChannelRegistry::class)->register('shopee', ShopeeConnector::class);
        ShopeeFx::configure();
        config(['integrations.shopee.document_poll_attempts' => 1, 'integrations.shopee.document_poll_sleep_ms' => 0]);
        config(['media.disk' => 'public']);
        Storage::fake('public');

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'shopee', 'external_shop_id' => '55',
            'shop_name' => 'SP', 'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'ACCESS_1', 'refresh_token' => 'rk', 'token_expires_at' => now()->addDays(7),
        ]);
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'source' => 'shopee', 'channel_account_id' => $account->getKey(),
            'external_order_id' => 'SN_1', 'order_number' => 'SN_1',
            'status' => StandardOrderStatus::Processing, 'raw_status' => 'PROCESSED',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now()->subHour(), 'source_updated_at' => now()->subHour(),
            'tags' => [], 'carrier' => 'SPX Express', 'packages' => [['externalPackageId' => 'PKG_1']],
        ]);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'order_id' => $order->getKey(), 'carrier' => 'SPX Express',
            'tracking_no' => null, 'package_no' => 'PKG_1', 'status' => Shipment::STATUS_CREATED,
            'cod_amount' => 0, 'label_path' => null, 'label_fetch_next_retry_at' => null, 'raw' => [],
        ]);

        Http::fake([
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFx::trackingNumber(), 200),
            '*/api/v2/logistics/get_shipping_document_parameter*' => Http::response(ShopeeFx::documentParameter(), 200),
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFx::createDocument(), 200),
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFx::documentResult('READY'), 200),
            '*/api/v2/logistics/download_shipping_document*' => Http::response('%PDF-1.4 shopee-label', 200, ['Content-Type' => 'application/pdf']),
            '*' => Http::response([], 200),
        ]);

        // Mô phỏng WORKER: KHÔNG có tenant request-bound.
        app(CurrentTenant::class)->clear();

        (new FetchChannelLabel((int) $shipment->getKey()))->handle(
            app(ShipmentService::class),
            app(CurrentTenant::class),
        );

        $this->assertNotNull(
            Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->label_path,
            'FetchChannelLabel phải kéo + lưu được tem dù chạy trong worker không có tenant (runAs).'
        );
    }
}
