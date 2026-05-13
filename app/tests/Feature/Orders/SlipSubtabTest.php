<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Tình trạng phiếu giao hàng" sub-tabs ở tab "Đang xử lý" — phân loại theo *trạng thái fetch thực* của
 * tem (SPEC 0013 cập nhật 2026-05-14). 3 tab tách rời, KHÔNG overlap:
 *   - "Có thể in"          : `shipments.label_path` set (R2 đã có PDF).
 *   - "Đang tải lại"       : `label_path` rỗng & `label_fetch_next_retry_at > NOW()` (`FetchChannelLabel` job đang queue).
 *   - "Nhận phiếu giao hàng": `label_path` rỗng & retry-job exhausted / chưa từng queue (user retry thủ công).
 *
 * Chuyển trạng thái: chỉ FE bulk action "Nhận lại phiếu" mới hiện ở "Nhận phiếu giao hàng"; "Có thể in" giấu
 * nút này (đơn đã có tem — nút sai context).
 */
class SlipSubtabTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'laz-1', 'shop_name' => 'Lazada',
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeProcessingOrderWithShipment(string $extId, ?string $labelPath, ?\Carbon\Carbon $nextRetryAt): Order
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'lazada',
            'channel_account_id' => $this->account->getKey(),
            'external_order_id' => $extId, 'order_number' => $extId,
            'status' => StandardOrderStatus::Processing, 'raw_status' => 'packed',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now()->subHours(1), 'tags' => [], 'carrier' => 'LEX VN',
            'source_updated_at' => now()->subHours(1),
        ]);
        Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'carrier' => 'LEX VN', 'tracking_no' => 'TRK-'.$extId,
            'status' => Shipment::STATUS_CREATED, 'cod_amount' => 0,
            'label_path' => $labelPath,
            'label_url' => $labelPath ? 'https://r2/'.$labelPath : null,
            'label_fetch_next_retry_at' => $nextRetryAt,
        ]);

        return $order;
    }

    public function test_slip_subtabs_split_by_label_state_and_next_retry_at(): void
    {
        // Setup 3 đơn tương ứng 3 sub-tab:
        // - "printable": có label_path
        // - "loading"  : label rỗng, next_retry_at trong tương lai (queue chưa exhaust)
        // - "failed"   : label rỗng, next_retry_at null (chưa từng queue HOẶC đã exhaust)
        $this->makeProcessingOrderWithShipment('LZ-PRINT', 'tenants/1/labels/print.pdf', null);
        $this->makeProcessingOrderWithShipment('LZ-LOAD', null, now()->addMinutes(2));
        $this->makeProcessingOrderWithShipment('LZ-FAIL', null, null);

        $stats = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing')->assertOk();

        $this->assertSame(1, $stats->json('data.by_slip.printable'), '"Có thể in" = 1 đơn có label_path.');
        $this->assertSame(1, $stats->json('data.by_slip.loading'), '"Đang tải lại" = 1 đơn label rỗng & next_retry_at > NOW().');
        $this->assertSame(1, $stats->json('data.by_slip.failed'), '"Nhận phiếu giao hàng" = 1 đơn label rỗng & retry exhausted/never-queued.');

        $this->assertSame(1, $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&slip=printable')->assertOk()
            ->json('meta.pagination.total'));
        $this->assertSame(1, $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&slip=loading')->assertOk()
            ->json('meta.pagination.total'));
        $this->assertSame(1, $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&slip=failed')->assertOk()
            ->json('meta.pagination.total'));
    }

    public function test_loading_subtab_excludes_orders_whose_next_retry_already_passed(): void
    {
        // Job dispatch với delay 15s nhưng đã quá thời điểm retry kế ⇒ phải rơi xuống `failed`
        // (queue chắc đã exhaust / queue worker dead). Không kẹt ở `loading` mãi.
        $this->makeProcessingOrderWithShipment('LZ-STALE', null, now()->subMinutes(5));
        $stats = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing')->assertOk();
        $this->assertSame(0, $stats->json('data.by_slip.loading'));
        $this->assertSame(1, $stats->json('data.by_slip.failed'));
    }

    public function test_printable_takes_precedence_when_order_has_both_labelled_and_unlabelled_shipments(): void
    {
        // Edge case: 1 đơn có 2 vận đơn open — 1 có label, 1 đang loading. Đơn vào "printable" (vì có ≥1 tem
        // sẵn để in); KHÔNG đếm vào loading/failed (whereDoesntHave openLabelled chặn).
        $order = $this->makeProcessingOrderWithShipment('LZ-MIXED', 'tenants/1/labels/mixed.pdf', null);
        Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'carrier' => 'LEX VN', 'tracking_no' => 'TRK-EXTRA',
            'status' => Shipment::STATUS_CREATED, 'cod_amount' => 0,
            'label_path' => null, 'label_fetch_next_retry_at' => now()->addMinutes(2),
        ]);
        $stats = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing')->assertOk();
        $this->assertSame(1, $stats->json('data.by_slip.printable'));
        $this->assertSame(0, $stats->json('data.by_slip.loading'));
        $this->assertSame(0, $stats->json('data.by_slip.failed'));
    }
}
