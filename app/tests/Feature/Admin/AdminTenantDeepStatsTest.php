<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;
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
}
