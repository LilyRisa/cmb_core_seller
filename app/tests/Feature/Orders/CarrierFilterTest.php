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
 * Regression test for "non-DBS Lazada carrier chips show count > 0 but list empty".
 * Reproduces the data shape that broke: order.carrier from initial Lazada sync = "Standard Delivery"
 * (item-level shipment_provider before pack), shipment.carrier after /order/pack = "LEX VN" (Lazada
 * remapped the provider). User clicks "LEX VN" chip — chip groupby & list filter must both follow
 * the shipment's carrier, not the stale orders.carrier denormalization.
 */
class CarrierFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private ChannelAccount $lazadaAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
        $this->lazadaAccount = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'laz-1', 'shop_name' => 'Lazada A',
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeOrder(string $extId, ?string $orderCarrier, ?string $shipmentCarrier, ?string $shipmentStatus = Shipment::STATUS_CREATED): Order
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'lazada',
            'channel_account_id' => $this->lazadaAccount->getKey(),
            'external_order_id' => $extId, 'order_number' => $extId,
            'status' => StandardOrderStatus::Processing, 'raw_status' => 'packed',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now()->subHours(1), 'tags' => [],
            'carrier' => $orderCarrier,
            'source_updated_at' => now()->subHours(1),
        ]);
        if ($shipmentCarrier !== null) {
            Shipment::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
                'carrier' => $shipmentCarrier, 'tracking_no' => 'TRK-'.$extId,
                'status' => $shipmentStatus, 'cod_amount' => 0,
            ]);
        }

        return $order;
    }

    public function test_carrier_filter_matches_shipment_carrier_even_when_orders_carrier_is_stale(): void
    {
        // Scenario: 3 Lazada orders in `processing` — order.carrier denormalization is stale ("Standard
        // Delivery" from initial sync) while the shipment created during "Chuẩn bị hàng" has the real
        // carrier ("LEX VN") that Lazada mapped at /order/pack. DBS is the control: order.carrier and
        // shipment.carrier match because it's set at order creation and doesn't get remapped.
        $this->makeOrder('LZ-A', 'Standard Delivery', 'LEX VN');
        $this->makeOrder('LZ-B', 'Standard Delivery', 'LEX VN');
        $this->makeOrder('LZ-C', 'Delivered by Seller', 'Delivered by Seller');

        $res = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing&source=lazada')
            ->assertOk();
        $byCarrier = collect($res->json('data.by_carrier'))->keyBy('carrier');

        $this->assertSame(2, $byCarrier['LEX VN']['count'] ?? null, 'Chip "LEX VN" must count the 2 orders whose open shipment.carrier=LEX VN.');
        $this->assertSame(1, $byCarrier['Delivered by Seller']['count'] ?? null, 'Chip "Delivered by Seller" still counts DBS order.');
        $this->assertArrayNotHasKey('Standard Delivery', $byCarrier->all(), 'Stale orders.carrier must not appear as a separate chip when a shipment override exists.');

        $list = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&source=lazada&carrier=LEX%20VN')
            ->assertOk();
        $this->assertSame(2, $list->json('meta.pagination.total'), 'Clicking "LEX VN" chip must list the 2 orders (matches via shipment.carrier).');

        $dbsList = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&source=lazada&carrier=Delivered%20by%20Seller')
            ->assertOk();
        $this->assertSame(1, $dbsList->json('meta.pagination.total'));
    }

    public function test_user_reported_bug_chip_shows_count_but_click_returns_empty(): void
    {
        // Reproduce the exact scenario the user reports: on "Đang xử lý" tab + source=lazada, all
        // non-DBS carrier chips show count > 0 but clicking returns empty list. The shape that
        // breaks: order.carrier from initial pending-sync (item shipment_provider) ≠ shipment.carrier
        // assigned at /order/pack. Without my OR-fallback in applyFilters, the list would only match
        // order.carrier and miss orders whose only "LEX VN" signal lives on the shipment.
        //
        // Setup: 5 non-DBS orders with various drift patterns + 2 DBS (control).
        $this->makeOrder('LZ-1', null, 'LEX VN');                            // No order.carrier yet
        $this->makeOrder('LZ-2', 'Standard Delivery', 'LEX VN');             // Remap drift
        $this->makeOrder('LZ-3', 'LEX VN', 'LEX VN');                        // Consistent
        $this->makeOrder('LZ-4', 'GHN-Express', 'Giao Hang Nhanh');          // Remap drift (GHN-Express → Giao Hang Nhanh)
        $this->makeOrder('LZ-5', 'GHN-Express', null);                       // No shipment yet
        $this->makeOrder('LZ-DBS-1', 'Delivered by Seller', 'Delivered by Seller');
        $this->makeOrder('LZ-DBS-2', 'Delivered by Seller', null);

        $stats = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing&source=lazada')
            ->assertOk();
        $byCarrier = collect($stats->json('data.by_carrier'))->keyBy('carrier');

        // For every chip the API exposes, clicking it MUST return exactly that count of orders.
        // This is the core invariant the user reports as broken.
        foreach ($byCarrier as $name => $entry) {
            $list = $this->actingAs($this->user)->withHeaders($this->header())
                ->getJson('/api/v1/orders?status=processing&source=lazada&carrier='.urlencode($name))
                ->assertOk();
            $this->assertSame(
                $entry['count'],
                $list->json('meta.pagination.total'),
                "Carrier chip '{$name}' shows count={$entry['count']} but clicking returns {$list->json('meta.pagination.total')}. Chip and list must be consistent."
            );
        }
    }

    public function test_carrier_filter_preserves_commas_in_carrier_names(): void
    {
        // Lazada returns carrier names like "Pickup: LEX VN, Delivery: LEX VN" — they contain commas.
        // The earlier explode(',', $carrier) split this into 2 useless tokens and matched zero orders,
        // even though the chip groupby kept the comma value intact and showed a count.
        $carrierName = 'Pickup: LEX VN, Delivery: LEX VN';
        $this->makeOrder('LZ-C1', $carrierName, $carrierName);
        $this->makeOrder('LZ-C2', null, $carrierName);
        $this->makeOrder('LZ-C3', $carrierName, null);

        $stats = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing&source=lazada')
            ->assertOk();
        $byCarrier = collect($stats->json('data.by_carrier'))->keyBy('carrier');
        $this->assertSame(3, $byCarrier[$carrierName]['count'] ?? null, 'Chip with carrier-name-containing-comma must group all 3 orders');

        $list = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&source=lazada&carrier='.urlencode($carrierName))
            ->assertOk();
        $this->assertSame(3, $list->json('meta.pagination.total'), 'Clicking the comma-in-name chip must list all 3 orders (must NOT explode the comma)');
    }

    public function test_carrier_filter_does_not_double_count_orders_with_multiple_open_shipments(): void
    {
        // Off-by-one scenario: an order with 2 open shipments under DIFFERENT carriers. The chip
        // uses MAX(carrier) so the order is counted under ONE chip; the list filter must also
        // assign the order to ONE effective carrier (not match it under both).
        $order = $this->makeOrder('LZ-DUAL', null, 'Delivered by Seller');
        Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'carrier' => 'LEX VN', 'tracking_no' => 'TRK-EXTRA',
            'status' => Shipment::STATUS_CREATED, 'cod_amount' => 0,
        ]);

        $stats = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing&source=lazada')
            ->assertOk();
        $byCarrier = collect($stats->json('data.by_carrier'))->keyBy('carrier');
        $totalChipCount = $byCarrier->sum('count');
        $this->assertSame(1, $totalChipCount, 'Order with 2 open shipments must appear in exactly ONE chip (not double-counted)');

        // Whichever chip it's in, click must return exactly 1 (matches chip count).
        foreach ($byCarrier as $name => $entry) {
            $list = $this->actingAs($this->user)->withHeaders($this->header())
                ->getJson('/api/v1/orders?status=processing&source=lazada&carrier='.urlencode($name))
                ->assertOk();
            $this->assertSame($entry['count'], $list->json('meta.pagination.total'), "Chip '{$name}' shows count={$entry['count']} but list returns {$list->json('meta.pagination.total')}.");
        }

        // The OTHER carrier (not the MAX) must NOT match this order.
        $otherCarrier = $byCarrier->keys()->first() === 'LEX VN' ? 'Delivered by Seller' : 'LEX VN';
        $list = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&source=lazada&carrier='.urlencode($otherCarrier))
            ->assertOk();
        $this->assertSame(0, $list->json('meta.pagination.total'), "Chip '{$otherCarrier}' is absent (or 0), list must also be 0.");
    }

    public function test_stats_endpoint_does_not_500_when_carrier_filter_is_active(): void
    {
        // When `?carrier=X` is in the URL, stats' $statusBase also picks up the carrier filter
        // (it skips 'status'/'stage'/'slip'/... but NOT 'carrier'). My leftJoinSub injects a join +
        // select that conflicts with stats' own `selectRaw('status, count(*)')` calls when the two
        // selects are merged instead of replaced. Reported by user as a 500 on
        // /orders/stats?status=processing&carrier=Drop-off:+LEX+VN,+Delivery:+LEX+V
        $carrierName = 'Drop-off: LEX VN, Delivery: LEX VN';
        $this->makeOrder('LZ-S1', $carrierName, $carrierName);
        $this->makeOrder('LZ-S2', null, $carrierName);

        $res = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing&carrier='.urlencode($carrierName))
            ->assertOk();
        $this->assertSame(2, $res->json('data.total'));
        $this->assertSame(2, $res->json('data.by_status.processing'));
    }

    public function test_carrier_filter_matches_orders_carrier_when_no_shipment_exists(): void
    {
        // Orders without shipment yet (pre-"Chuẩn bị hàng") must still appear under orders.carrier.
        $this->makeOrder('LZ-D', 'GHN', null);
        $this->makeOrder('LZ-E', 'GHN', null);

        $res = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats?status=processing&source=lazada')
            ->assertOk();
        $byCarrier = collect($res->json('data.by_carrier'))->keyBy('carrier');
        $this->assertSame(2, $byCarrier['GHN']['count'] ?? null);

        $list = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing&source=lazada&carrier=GHN')
            ->assertOk();
        $this->assertSame(2, $list->json('meta.pagination.total'));
    }
}
