<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Tenant $otherTenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'My shop']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
        $this->otherTenant = Tenant::create(['name' => 'Someone else']);

        $account = $this->makeAccount($this->tenant);
        $this->makeOrder($this->tenant, $account, 'TT-1001', StandardOrderStatus::Pending, grandTotal: 250000, placedAt: now()->subDays(1));
        $this->makeOrder($this->tenant, $account, 'TT-1002', StandardOrderStatus::Shipped, grandTotal: 90000, placedAt: now()->subDays(3));
        $this->makeOrder($this->tenant, $account, 'TT-1003', StandardOrderStatus::Cancelled, grandTotal: 50000, placedAt: now()->subHours(2), hasIssue: true);
        // an order belonging to a different tenant — must never leak
        $this->makeOrder($this->otherTenant, $this->makeAccount($this->otherTenant), 'OTHER-1', StandardOrderStatus::Pending, grandTotal: 999999, placedAt: now());
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeAccount(Tenant $tenant): ChannelAccount
    {
        return ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'tiktok',
            'external_shop_id' => 'shop-'.$tenant->getKey(), 'shop_name' => 'Shop '.$tenant->getKey(),
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function makeOrder(Tenant $tenant, ChannelAccount $account, string $extId, StandardOrderStatus $status, int $grandTotal, $placedAt, bool $hasIssue = false): Order
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $account->getKey(),
            'external_order_id' => $extId, 'order_number' => $extId, 'status' => $status, 'raw_status' => 'X',
            'currency' => 'VND', 'grand_total' => $grandTotal, 'item_total' => $grandTotal, 'placed_at' => $placedAt,
            'has_issue' => $hasIssue, 'tags' => [], 'source_updated_at' => $placedAt,
        ]);
        OrderItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'order_id' => $order->getKey(), 'external_item_id' => 'li-'.$extId,
            'seller_sku' => 'SKU-'.$extId, 'name' => 'Item '.$extId, 'quantity' => 2, 'unit_price' => (int) ($grandTotal / 2), 'subtotal' => $grandTotal,
        ]);

        return $order;
    }

    public function test_index_lists_only_current_tenant_orders(): void
    {
        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders')->assertOk();

        $res->assertJsonCount(3, 'data')->assertJsonPath('meta.pagination.total', 3);
        $numbers = collect($res->json('data'))->pluck('order_number')->all();
        $this->assertEqualsCanonicalizing(['TT-1001', 'TT-1002', 'TT-1003'], $numbers);
        $this->assertNotContains('OTHER-1', $numbers);
        // canonical status + label + counts surfaced
        $first = collect($res->json('data'))->firstWhere('order_number', 'TT-1001');
        $this->assertSame('pending', $first['status']);
        $this->assertSame('Chờ xử lý', $first['status_label']);
        $this->assertSame(1, $first['items_count']); // one line item in the list view (qty is in the detail)
    }

    public function test_index_filters_and_sorts(): void
    {
        // by status
        $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders?status=shipped')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.order_number', 'TT-1002');
        // by has_issue
        $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders?has_issue=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.order_number', 'TT-1003');
        // search by order number
        $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders?q=TT-1001')
            ->assertOk()->assertJsonCount(1, 'data');
        // sort by grand_total ascending
        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders?sort=grand_total')->assertOk();
        $this->assertSame(['TT-1003', 'TT-1002', 'TT-1001'], collect($res->json('data'))->pluck('order_number')->all());
    }

    public function test_show_includes_items_and_status_history(): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1001')->first();
        $this->actingAs($this->user)->withHeaders($this->header())->getJson("/api/v1/orders/{$order->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.order_number', 'TT-1001')
            ->assertJsonStructure(['data' => ['items', 'status_history', 'shipping_address']]);
    }

    public function test_show_404_for_another_tenants_order(): void
    {
        $other = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'OTHER-1')->first();
        $this->actingAs($this->user)->withHeaders($this->header())->getJson("/api/v1/orders/{$other->getKey()}")->assertNotFound();
    }

    public function test_stats_returns_counts_by_status(): void
    {
        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders/stats')->assertOk();
        $this->assertSame(3, $res->json('data.total'));
        $this->assertSame(1, $res->json('data.has_issue'));
        $this->assertSame(1, $res->json('data.by_status.pending'));
        $this->assertSame(1, $res->json('data.by_status.shipped'));
        $this->assertSame(1, $res->json('data.by_status.cancelled'));
        $this->assertSame(0, $res->json('data.by_status.completed'));
    }

    public function test_update_tags_and_note(): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1001')->first();

        $this->actingAs($this->user)->withHeaders($this->header())
            ->postJson("/api/v1/orders/{$order->getKey()}/tags", ['add' => ['urgent', 'gift'], 'remove' => ['nope']])
            ->assertOk()->assertJsonPath('data.tags', ['urgent', 'gift']);

        $this->actingAs($this->user)->withHeaders($this->header())
            ->postJson("/api/v1/orders/{$order->getKey()}/tags", ['remove' => ['gift']])
            ->assertOk()->assertJsonPath('data.tags', ['urgent']);

        $this->actingAs($this->user)->withHeaders($this->header())
            ->patchJson("/api/v1/orders/{$order->getKey()}/note", ['note' => 'Gọi trước khi giao'])
            ->assertOk()->assertJsonPath('data.note', 'Gọi trước khi giao');

        $this->assertSame(['urgent'], $order->fresh()->tags);
        $this->assertSame('Gọi trước khi giao', $order->fresh()->note);
    }

    public function test_viewer_cannot_update_tags(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $order = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1001')->first();

        $this->actingAs($viewer)->withHeaders($this->header())->getJson('/api/v1/orders')->assertOk(); // can view
        $this->actingAs($viewer)->withHeaders($this->header())->postJson("/api/v1/orders/{$order->getKey()}/tags", ['add' => ['x']])->assertForbidden();
    }

    public function test_orders_carry_estimated_profit_after_platform_fee(): void
    {
        // configure the TikTok platform fee % (stored in tenant.settings.platform_fee_pct — SPEC 0012)
        $this->actingAs($this->user)->withHeaders($this->header())
            ->patchJson('/api/v1/tenant', ['settings' => ['platform_fee_pct' => ['tiktok' => 5]]])->assertOk();

        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders')->assertOk();
        $o = collect($res->json('data'))->firstWhere('order_number', 'TT-1001');
        $this->assertNotNull($o['profit']);
        $this->assertEquals(5, $o['profit']['platform_fee_pct']);
        $this->assertSame((int) round(250000 * 0.05), $o['profit']['platform_fee']);   // 12 500
        $this->assertFalse($o['profit']['cost_complete']);                              // no master SKU linked yet ⇒ COGS unknown
        $this->assertSame(250000 - 12500 - 0 - 0, $o['profit']['estimated_profit']);

        // and on the detail endpoint
        $id = $o['id'];
        $this->actingAs($this->user)->withHeaders($this->header())->getJson("/api/v1/orders/{$id}?include=items")
            ->assertOk()->assertJsonPath('data.profit.platform_fee', 12500);
    }

    public function test_can_create_an_invoice_print_job_for_orders(): void
    {
        Storage::fake('public');
        Http::fake(['*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake-invoice', 200, ['Content-Type' => 'application/pdf'])]);
        // Đơn sàn TMĐT có hoá đơn điện tử của sàn ⇒ không in hoá đơn nội bộ; chỉ đơn manual mới in được.
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'channel_account_id' => null,
            'external_order_id' => 'MAN-INV-1', 'order_number' => 'MAN-INV-1',
            'status' => \CMBcoreSeller\Support\Enums\StandardOrderStatus::Pending, 'raw_status' => 'pending', 'source_updated_at' => now(),
            'grand_total' => 100000, 'item_total' => 100000, 'shipping_fee' => 0, 'currency' => 'VND',
        ]);

        $jobId = $this->actingAs($this->user)->withHeaders($this->header())
            ->postJson('/api/v1/print-jobs', ['type' => 'invoice', 'order_ids' => [$order->getKey()]])
            ->assertCreated()->assertJsonPath('data.type', 'invoice')->json('data.id');
        // sync queue → RenderPrintJob already produced the PDF
        $this->actingAs($this->user)->withHeaders($this->header())->getJson("/api/v1/print-jobs/{$jobId}")
            ->assertOk()->assertJsonPath('data.status', 'done')->assertJsonPath('data.meta.orders', 1);
    }

    public function test_dashboard_summary(): void
    {
        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/dashboard/summary')->assertOk();
        $this->assertSame(1, $res->json('data.channel_accounts.total')); // only this tenant's account
        $this->assertSame(3, $res->json('data.orders.total'));
        $this->assertSame(1, $res->json('data.orders.has_issue'));
    }
}
