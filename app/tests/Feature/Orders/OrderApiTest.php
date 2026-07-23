<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
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
        // search by MÃ VẬN ĐƠN (tracking_no) — gõ chữ thường vẫn khớp (không phân biệt hoa/thường)
        Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1002')->value('id'),
            'carrier' => 'ghn', 'tracking_no' => 'GHN-TRACK-9', 'status' => 'created',
        ]);
        $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders?q=ghn-track-9')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.order_number', 'TT-1002');
        // sort by grand_total ascending
        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders?sort=grand_total')->assertOk();
        $this->assertSame(['TT-1003', 'TT-1002', 'TT-1001'], collect($res->json('data'))->pluck('order_number')->all());
    }

    public function test_index_filters_by_explicit_ids_ignoring_other_filters(): void
    {
        // "Chuẩn bị hàng" bulk modal needs to poll the exact tracked order ids regardless of the
        // current tab/status filter (the orders may have already moved out of that tab/status).
        $pending = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1001')->firstOrFail();
        $shipped = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1002')->firstOrFail();
        $cancelled = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1003')->firstOrFail();

        // status filter would normally exclude the shipped order — ids must override/ignore it.
        $res = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson("/api/v1/orders?status=pending&ids={$pending->id},{$shipped->id}")
            ->assertOk();
        $numbers = collect($res->json('data'))->pluck('order_number')->all();
        $this->assertEqualsCanonicalizing(['TT-1001', 'TT-1002'], $numbers);
        $this->assertNotContains('TT-1003', $numbers);

        // a non-numeric/garbage id is ignored rather than erroring
        $res2 = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson("/api/v1/orders?ids={$cancelled->id},not-a-number")
            ->assertOk();
        $this->assertSame(['TT-1003'], collect($res2->json('data'))->pluck('order_number')->all());

        // another tenant's order id must never leak even if explicitly requested
        $otherOrder = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'OTHER-1')->firstOrFail();
        $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson("/api/v1/orders?ids={$otherOrder->id}")
            ->assertOk()->assertJsonCount(0, 'data');
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

    public function test_update_tags_response_still_includes_full_order_detail(): void
    {
        // Bug thật: sau khi thêm nhãn, ảnh sản phẩm + thông tin đơn biến mất khỏi màn
        // chi tiết cho tới khi mở lại trang. Root cause: updateTags() chỉ loadCount('items')
        // thay vì load đủ quan hệ như show() ⇒ response thiếu items/thumbnail/status_history.
        $order = Order::withoutGlobalScope(TenantScope::class)->where('order_number', 'TT-1001')->first();
        OrderItem::withoutGlobalScope(TenantScope::class)->where('order_id', $order->getKey())
            ->update(['image' => 'https://example.test/product.jpg']);

        $res = $this->actingAs($this->user)->withHeaders($this->header())
            ->postJson("/api/v1/orders/{$order->getKey()}/tags", ['add' => ['urgent']])
            ->assertOk();

        $res->assertJsonPath('data.thumbnail', 'https://example.test/product.jpg');
        $res->assertJsonCount(1, 'data.items');
        $res->assertJsonPath('data.items.0.name', 'Item TT-1001');
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

    public function test_orders_carry_detailed_estimated_fee_breakdown_when_no_flat_pct_configured(): void
    {
        // KHÔNG cấu hình platform_fee_pct ⇒ dùng biểu phí mặc định config('orders.fee_rates').
        // TikTok: hoa hồng 14% (trên item_total 250000) + giao dịch 6% (trên grand 250000) + cố định 3.000đ.
        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders')->assertOk();
        $o = collect($res->json('data'))->firstWhere('order_number', 'TT-1001');

        $this->assertNotNull($o['profit']);
        $this->assertSame('estimate', $o['profit']['fee_source']);
        $byType = collect($o['profit']['fee_breakdown'])->keyBy('type');
        $this->assertSame((int) round(250000 * 0.14), $byType['commission']['amount']);   // 35 000
        $this->assertSame((int) round(250000 * 0.06), $byType['transaction']['amount']);   // 15 000
        $this->assertSame(3000, $byType['fixed']['amount']);
        $this->assertSame(35000 + 15000 + 3000, $o['profit']['platform_fee']);             // 53 000
        $this->assertSame(250000 - 53000 - 0 - 0, $o['profit']['estimated_profit']);
    }

    public function test_platform_voucher_is_added_back_to_profit_revenue(): void
    {
        // Voucher SÀN cấp 30k: grand_total = số khách trả (đã trừ voucher sàn). Vì sàn HOÀN
        // khoản này cho shop nên phải cộng lại vào doanh thu khi tính lãi (không thể lấy giá
        // sau voucher sàn để tính). Voucher shop tự chịu (seller_discount) thì KHÔNG cộng.
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->firstOrFail();
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $account->getKey(),
            'external_order_id' => 'TT-VOUCHER', 'order_number' => 'TT-VOUCHER', 'status' => StandardOrderStatus::Pending, 'raw_status' => 'X',
            'currency' => 'VND', 'grand_total' => 250000, 'item_total' => 250000, 'platform_discount' => 30000, 'seller_discount' => 0,
            'placed_at' => now(), 'tags' => [], 'source_updated_at' => now(),
        ]);

        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders')->assertOk();
        $o = collect($res->json('data'))->firstWhere('order_number', 'TT-VOUCHER');

        // platform_fee: hoa hồng 14%×250k + giao dịch 6%×250k + cố định 3k = 53k.
        $this->assertSame(53000, $o['profit']['platform_fee']);
        $this->assertSame(30000, $o['profit']['platform_subsidy']);
        // doanh thu = grand_total(250k) + voucher sàn(30k) = 280k ⇒ lãi = 280k − 53k = 227k.
        $this->assertSame(280000 - 53000 - 0 - 0, $o['profit']['estimated_profit']);
    }

    public function test_shopee_platform_voucher_from_settlement_is_added_back_to_profit(): void
    {
        // Shopee: voucher SÀN chỉ lộ ở đối soát escrow (get_order_detail không có). Khi có
        // settlement line `voucher_platform` ⇒ cộng lại vào doanh thu khi tính lãi (được sàn hoàn).
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee',
            'external_shop_id' => 'sp-'.$this->tenant->getKey(), 'shop_name' => 'SP', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'shopee', 'channel_account_id' => $account->getKey(),
            'external_order_id' => 'SP-1', 'order_number' => 'SP-1', 'status' => StandardOrderStatus::Shipped, 'raw_status' => 'X',
            'currency' => 'VND', 'grand_total' => 70000, 'item_total' => 100000, 'platform_discount' => 0, 'seller_discount' => 0,
            'shipping_fee' => 0, 'placed_at' => now(), 'tags' => [], 'source_updated_at' => now(),
        ]);
        $settlement = Settlement::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->getKey(), 'currency' => 'VND',
            'external_id' => 'ST-1', 'period_start' => now()->subDay(), 'period_end' => now(), 'status' => 'reconciled',
        ]);
        foreach ([['commission', -12500], ['voucher_platform', 30000]] as [$type, $amt]) {
            SettlementLine::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $this->tenant->getKey(), 'settlement_id' => $settlement->getKey(), 'order_id' => $order->getKey(),
                'fee_type' => $type, 'amount' => $amt, 'created_at' => now(),
            ]);
        }

        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders')->assertOk();
        $o = collect($res->json('data'))->firstWhere('order_number', 'SP-1');
        $this->assertSame('settlement', $o['profit']['fee_source']);
        $this->assertSame(12500, $o['profit']['platform_fee']);       // phí thực từ đối soát
        $this->assertSame(30000, $o['profit']['platform_subsidy']);   // voucher sàn lấy từ escrow
        // doanh thu = grand(70k) + voucher sàn(30k) = 100k ⇒ lãi = 100k − 12.5k = 87.5k.
        $this->assertSame(100000 - 12500, $o['profit']['estimated_profit']);
    }

    public function test_optional_program_fee_appears_in_breakdown_when_enabled(): void
    {
        // Bật 1 phí chương trình tùy chọn (affiliate 5%) cho TikTok qua fee_rates ⇒ xuất hiện trong breakdown.
        $this->actingAs($this->user)->withHeaders($this->header())
            ->patchJson('/api/v1/tenant', ['settings' => ['fee_rates' => ['tiktok' => [
                'commission_pct' => 14, 'transaction_pct' => 6, 'fixed_fee' => 3000,
                'programs' => [['key' => 'affiliate', 'enabled' => true, 'rate' => 5]],
            ]]]])->assertOk();

        $res = $this->actingAs($this->user)->withHeaders($this->header())->getJson('/api/v1/orders')->assertOk();
        $o = collect($res->json('data'))->firstWhere('order_number', 'TT-1001');
        $byType = collect($o['profit']['fee_breakdown'])->keyBy('type');
        $this->assertSame((int) round(250000 * 0.05), $byType['program:affiliate']['amount']);   // 12 500
        $this->assertSame(35000 + 15000 + 3000 + 12500, $o['profit']['platform_fee']);            // 65 500
    }

    public function test_can_create_an_invoice_print_job_for_orders(): void
    {
        Storage::fake('public');
        Http::fake(['*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake-invoice', 200, ['Content-Type' => 'application/pdf'])]);
        // Đơn sàn TMĐT có hoá đơn điện tử của sàn ⇒ không in hoá đơn nội bộ; chỉ đơn manual mới in được.
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'channel_account_id' => null,
            'external_order_id' => 'MAN-INV-1', 'order_number' => 'MAN-INV-1',
            'status' => StandardOrderStatus::Pending, 'raw_status' => 'pending', 'source_updated_at' => now(),
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
