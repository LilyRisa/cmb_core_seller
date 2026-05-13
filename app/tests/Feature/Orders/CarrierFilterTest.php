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
