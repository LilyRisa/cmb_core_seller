<?php

namespace Tests\Feature\Reports;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Reports API — Phase 6.1 / SPEC 0015. Smoke test cho 3 endpoint + RBAC + CSV export.
 */
class ReportsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private Warehouse $wh;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        Http::fake();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop R']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->wh = Warehouse::defaultFor((int) $this->tenant->getKey());
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A', 'name' => 'Áo M']);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 50);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function createAndShipOrder(int $qty, int $price): int
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'X', 'phone' => '0900000099'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => $qty, 'unit_price' => $price]],
        ])->assertCreated()->json('data.id');
        $shId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/ship", ['tracking_no' => 'T-'.uniqid()])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$shId]])->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/handover', ['shipment_ids' => [$shId]])->assertOk();

        return (int) $orderId;
    }

    public function test_revenue_profit_top_products_endpoints(): void
    {
        // SKU đã có 50 tồn (giá vốn 0 do `adjust` không qua phiếu nhập). Cài giá vốn average qua last_receipt_cost.
        $this->sku->update(['cost_price' => 60000, 'last_receipt_cost' => 60000, 'cost_method' => 'average']);

        $this->createAndShipOrder(2, 150000);   // doanh thu 300000, COGS 2×60000=120000
        $this->createAndShipOrder(3, 150000);   // doanh thu 450000, COGS 3×60000=180000

        $rev = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/revenue?granularity=day')->assertOk()->json('data');
        $this->assertSame(2, $rev['totals']['orders']);
        $this->assertSame(750000, $rev['totals']['revenue']);
        $this->assertNotEmpty($rev['series']);

        $profit = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/profit')->assertOk()->json('data');
        $this->assertSame(2, $profit['totals']['orders']);
        $this->assertSame(750000, $profit['totals']['revenue']);   // ← bug 1: trước đó dùng unit_price×qty (đúng khi không có discount); test mới khoá lại
        $this->assertSame(300000, $profit['totals']['cogs']);   // 2 đơn × COGS theo synthetic FIFO (last_receipt_cost 60k)
        $this->assertSame(450000, $profit['totals']['gross_profit']);
        $this->assertSame(450000, $profit['totals']['net_profit']);   // chưa có settlement ⇒ net = gross
        $this->assertSame('none', $profit['totals']['fee_source']);
        $this->assertEquals(60.0, $profit['totals']['margin_pct']);   // 450/750 × 100; JSON có thể trả int hoặc float

        $top = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/top-products?limit=10')->assertOk()->json('data');
        $this->assertCount(1, $top['items']);
        $this->assertSame((int) $this->sku->getKey(), (int) $top['items'][0]['sku_id']);
        $this->assertSame(5, (int) $top['items'][0]['qty']);
        $this->assertSame(750000, (int) $top['items'][0]['revenue']);
        $this->assertSame(300000, (int) $top['items'][0]['cogs']);
        $this->assertSame(450000, (int) $top['items'][0]['gross_profit']);
    }

    public function test_profit_subtracts_actual_fees_from_settlement_lines(): void
    {
        $this->sku->update(['cost_price' => 60000, 'last_receipt_cost' => 60000, 'cost_method' => 'average']);
        $orderId = $this->createAndShipOrder(2, 150000);   // revenue 300000, COGS 120000

        // Phải có channel_account để Settlement gắn vào — test mặc định seed manual order; tạo 1 fake account.
        $acct = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok',
            'external_shop_id' => 's-1', 'shop_name' => 'S1', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
        $stm = Settlement::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $acct->getKey(),
            'external_id' => 'ST-1', 'period_start' => now()->subDay(), 'period_end' => now(),
            'currency' => 'VND', 'total_revenue' => 300000, 'total_fee' => 0, 'total_shipping_fee' => 0,
            'total_payout' => 0, 'status' => Settlement::STATUS_RECONCILED,
        ]);
        // commission −24000 (8%), shipping_fee −12000 — settlement_lines lưu số âm theo SPEC 0016.
        SettlementLine::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'settlement_id' => $stm->getKey(), 'order_id' => $orderId,
            'fee_type' => 'commission', 'amount' => -24000, 'occurred_at' => now(), 'created_at' => now(),
        ]);
        SettlementLine::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'settlement_id' => $stm->getKey(), 'order_id' => $orderId,
            'fee_type' => 'shipping_fee', 'amount' => -12000, 'occurred_at' => now(), 'created_at' => now(),
        ]);

        $profit = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/profit')->assertOk()->json('data');
        $this->assertSame(300000, $profit['totals']['revenue']);
        $this->assertSame(120000, $profit['totals']['cogs']);
        $this->assertSame(24000, $profit['totals']['fees']);          // abs(−24000)
        $this->assertSame(12000, $profit['totals']['shipping']);
        $this->assertSame(180000, $profit['totals']['gross_profit']);
        $this->assertSame(144000, $profit['totals']['net_profit']);   // 180000 − 24000 − 12000
        $this->assertSame('settlement', $profit['totals']['fee_source']);
        // margin tính trên net
        $this->assertEquals(48.0, $profit['totals']['margin_pct']);
    }

    public function test_top_products_respects_source_filter(): void
    {
        // Đơn manual (source=manual) + đơn "tiktok" cùng SKU → filter source=tiktok phải loại đơn manual.
        $this->createAndShipOrder(2, 100000);   // manual

        $acct = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok',
            'external_shop_id' => 's-2', 'shop_name' => 'S2', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
        // Tạo order tiktok thủ công + 1 item gắn SKU, status shipped để chắc chắn được tính.
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $acct->getKey(),
            'external_order_id' => 'TT-99', 'order_number' => 'TT-99', 'status' => StandardOrderStatus::Shipped,
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 500000, 'item_total' => 500000,
            'placed_at' => now(), 'shipped_at' => now(), 'source_updated_at' => now(),
        ]);
        $order->items()->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_id' => $this->sku->getKey(),
            'external_item_id' => 'li-99', 'seller_sku' => 'A', 'name' => 'Áo M',
            'quantity' => 5, 'unit_price' => 100000, 'subtotal' => 500000,
        ]);

        // Không filter ⇒ qty = 2 (manual) + 5 (tiktok) = 7
        $all = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/top-products')->assertOk()->json('data');
        $this->assertSame(7, (int) $all['items'][0]['qty']);

        // Filter source=tiktok ⇒ chỉ 5
        $tt = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/top-products?source=tiktok')->assertOk()->json('data');
        $this->assertSame(5, (int) $tt['items'][0]['qty']);
        $this->assertSame(500000, (int) $tt['items'][0]['revenue']);

        // Filter source=manual ⇒ chỉ 2
        $mn = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/top-products?source=manual')->assertOk()->json('data');
        $this->assertSame(2, (int) $mn['items'][0]['qty']);
    }

    public function test_returned_and_refunded_orders_excluded_from_revenue(): void
    {
        $this->createAndShipOrder(1, 100000);   // 1 đơn shipped, 100k revenue
        // Đơn trả hàng & hoàn tiền — phải bị loại khỏi báo cáo.
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'channel_account_id' => null,
            'external_order_id' => 'R-1', 'order_number' => 'R-1', 'status' => StandardOrderStatus::ReturnedRefunded,
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 999999, 'item_total' => 999999,
            'placed_at' => now(), 'source_updated_at' => now(),
        ]);
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'channel_account_id' => null,
            'external_order_id' => 'X-1', 'order_number' => 'X-1', 'status' => StandardOrderStatus::Cancelled,
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 555555, 'item_total' => 555555,
            'placed_at' => now(), 'source_updated_at' => now(),
        ]);

        $rev = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/revenue')->assertOk()->json('data');
        $this->assertSame(1, $rev['totals']['orders']);
        $this->assertSame(100000, $rev['totals']['revenue']);
    }

    public function test_export_csv_works_with_tenant_id_in_query_param(): void
    {
        // `<a href download>` không gửi được header → query param fallback (Bug 5).
        $this->createAndShipOrder(1, 100000);
        $resp = $this->actingAs($this->owner)
            ->get('/api/v1/reports/export?type=revenue&X-Tenant-Id='.$this->tenant->getKey());
        $resp->assertOk();
        $this->assertStringContainsString('text/csv', (string) $resp->headers->get('Content-Type'));
    }

    public function test_csv_export_streams_with_utf8_bom(): void
    {
        $this->createAndShipOrder(1, 100000);
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())->get('/api/v1/reports/export?type=revenue');
        $resp->assertOk();
        $body = $resp->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);   // UTF-8 BOM cho Excel
        $this->assertStringContainsString('Ngày,"Số đơn"', $body);
        $this->assertStringContainsString('"Doanh thu (VND)"', $body);
    }

    public function test_rbac_viewer_forbidden(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->getJson('/api/v1/reports/revenue')->assertForbidden();
        $this->actingAs($viewer)->withHeaders($this->h())->get('/api/v1/reports/export?type=profit')->assertForbidden();

        $acct = User::factory()->create();
        $this->tenant->users()->attach($acct->getKey(), ['role' => Role::Accountant->value]);
        $this->actingAs($acct)->withHeaders($this->h())->getJson('/api/v1/reports/revenue')->assertOk();
        $this->actingAs($acct)->withHeaders($this->h())->get('/api/v1/reports/export?type=revenue')->assertOk();
    }
}
