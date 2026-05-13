<?php

namespace Tests\Feature\Reports;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
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
        $this->createAndShipOrder(2, 150000);   // doanh thu 300000
        $this->createAndShipOrder(3, 150000);   // doanh thu 450000

        $rev = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/revenue?granularity=day')->assertOk()->json('data');
        $this->assertSame(2, $rev['totals']['orders']);
        $this->assertSame(750000, $rev['totals']['revenue']);
        $this->assertNotEmpty($rev['series']);

        $profit = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/profit')->assertOk()->json('data');
        $this->assertSame(2, $profit['totals']['orders']);

        $top = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/reports/top-products?limit=10')->assertOk()->json('data');
        $this->assertCount(1, $top['items']);
        $this->assertSame((int) $this->sku->getKey(), (int) $top['items'][0]['sku_id']);
        $this->assertSame(5, (int) $top['items'][0]['qty']);
        $this->assertSame(750000, (int) $top['items'][0]['revenue']);
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
