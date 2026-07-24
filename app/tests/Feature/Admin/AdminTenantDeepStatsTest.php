<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantDeepStatsTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
    }

    public function test_show_includes_sku_count(): void
    {
        $wh = Warehouse::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Kho', 'is_default' => true,
        ]);
        Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A1', 'name' => 'X',
            'warehouse_id' => $wh->getKey(), 'stock_on_hand' => 1, 'stock_reserved' => 0,
        ]);

        $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}")
            ->assertOk()->assertJsonPath('data.sku_count', 1);
    }

    public function test_daily_order_stats_groups_by_day(): void
    {
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'status' => 'processing',
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'tags' => [], 'shipping_address' => [], 'placed_at' => now(),
        ]);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/orders/daily-stats?days=7")
            ->assertOk();

        $rows = $res->json('data');
        $this->assertGreaterThanOrEqual(1, count($rows));
        $this->assertSame(1, collect($rows)->firstWhere('date', now()->format('Y-m-d'))['count']);
    }

    public function test_order_status_history_lists_changes(): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'status' => 'processing',
            'order_number' => 'M-1', 'raw_status' => 'X', 'currency' => 'VND',
            'grand_total' => 100000, 'item_total' => 100000, 'tags' => [], 'shipping_address' => [],
        ]);
        OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'from_status' => null, 'to_status' => 'processing', 'source' => 'user', 'changed_at' => now(),
        ]);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/order-status-history")
            ->assertOk();

        $this->assertSame('M-1', $res->json('data.0.order_number'));
    }

    public function test_audit_logs_endpoint_returns_all_actions_not_just_admin(): void
    {
        AuditLog::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'user_id' => 1,
            'action' => 'orders.status.change', 'ip' => '127.0.0.1',
        ]);
        AuditLog::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'user_id' => $this->admin->getKey(),
            'action' => 'admin.tenant.suspend', 'ip' => '127.0.0.1',
        ]);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/audit-logs")
            ->assertOk();

        $this->assertCount(2, $res->json('data'));
    }

    public function test_audit_logs_endpoint_filters_by_action_prefix(): void
    {
        AuditLog::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'user_id' => 1,
            'action' => 'orders.status.change', 'ip' => '127.0.0.1',
        ]);
        AuditLog::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'user_id' => $this->admin->getKey(),
            'action' => 'admin.tenant.suspend', 'ip' => '127.0.0.1',
        ]);

        // Không lọc — cả 2 dòng đều trả về.
        $resAll = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/audit-logs")
            ->assertOk();
        $this->assertCount(2, $resAll->json('data'));

        // Lọc action=admin. — chỉ dòng admin.* được trả về.
        $resFiltered = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/audit-logs?action=admin.")
            ->assertOk();
        $rows = $resFiltered->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('admin.tenant.suspend', $rows[0]['action']);
    }

    public function test_product_order_stats_counts_distinct_orders_per_mapped_sku(): void
    {
        $wh = Warehouse::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Kho', 'is_default' => true,
        ]);
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A1', 'name' => 'Áo thun',
            'warehouse_id' => $wh->getKey(), 'stock_on_hand' => 10, 'stock_reserved' => 0,
        ]);
        $this->createOrderWithItem(['sku_id' => $sku->getKey(), 'name' => 'Áo thun (TikTok)', 'quantity' => 2]);
        $this->createOrderWithItem(['sku_id' => $sku->getKey(), 'name' => 'Áo thun (Lazada)', 'quantity' => 1]);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/product-order-stats")
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame($sku->getKey(), $rows[0]['sku_id']);
        $this->assertTrue($rows[0]['mapped']);
        $this->assertSame(2, $rows[0]['order_count']);
        $this->assertSame(3, $rows[0]['qty']);
    }

    public function test_product_order_stats_groups_unmapped_items_separately_from_mapped(): void
    {
        $this->createOrderWithItem(['sku_id' => null, 'external_product_id' => 'ext-1', 'name' => 'SP chưa map']);
        $this->createOrderWithItem(['sku_id' => null, 'external_product_id' => 'ext-1', 'name' => 'SP chưa map']);
        $this->createOrderWithItem(['sku_id' => null, 'external_product_id' => 'ext-2', 'name' => 'SP khác']);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/product-order-stats")
            ->assertOk();

        $rows = collect($res->json('data'))->keyBy('name');
        $this->assertFalse($rows['SP chưa map']['mapped']);
        $this->assertSame(2, $rows['SP chưa map']['order_count']);
        $this->assertSame(1, $rows['SP khác']['order_count']);
    }

    public function test_product_order_stats_excludes_cancelled_orders(): void
    {
        $this->createOrderWithItem(['name' => 'SP bán được'], status: 'processing');
        $this->createOrderWithItem(['name' => 'SP bán được'], status: 'cancelled');

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/product-order-stats")
            ->assertOk();

        $this->assertSame(1, $res->json('data.0.order_count'));
    }

    public function test_product_order_stats_filters_by_search(): void
    {
        $this->createOrderWithItem(['name' => 'Áo thun nam']);
        $this->createOrderWithItem(['name' => 'Quần jean nữ']);

        // Tránh gõ ký tự có dấu viết hoa trong term tìm — SQLite LOWER() chỉ hạ ASCII (xem SkuSearch memory).
        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/product-order-stats?search=".urlencode('thun nam'))
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('Áo thun nam', $rows[0]['name']);
    }

    /** @param  array<string,mixed>  $itemOverrides */
    private function createOrderWithItem(array $itemOverrides = [], string $status = 'processing'): OrderItem
    {
        static $seq = 0;
        $seq++;

        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'status' => $status,
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'tags' => [], 'shipping_address' => [], 'placed_at' => now(),
        ]);

        return OrderItem::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'external_item_id' => "item-{$seq}", 'name' => 'SP', 'quantity' => 1,
            'unit_price' => 50000, 'subtotal' => 50000,
        ], $itemOverrides));
    }
}
