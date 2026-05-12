<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInventoryEffectsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 's1', 'shop_name' => 'S1', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function sku(string $code, int $onHand = 0): Sku
    {
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => $code, 'name' => $code]);
        if ($onHand > 0) {
            app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $sku->getKey(), null, $onHand);
        }

        return $sku;
    }

    private function level(Sku $sku): InventoryLevel
    {
        return InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $sku->getKey())->firstOrFail();
    }

    /** @param array<int,array<string,mixed>> $items */
    private function channelOrder(StandardOrderStatus $status, array $items): Order
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $this->shop->getKey(),
            'external_order_id' => 'O'.uniqid(), 'order_number' => 'O'.uniqid(), 'status' => $status, 'raw_status' => 'X',
            'shipping_address' => [], 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => now(),
            'has_issue' => false, 'tags' => [], 'source_updated_at' => now(), 'shipped_at' => $status === StandardOrderStatus::Shipped ? now() : null,
        ]);
        foreach ($items as $i => $it) {
            OrderItem::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(), 'external_item_id' => 'li'.$i,
                'external_sku_id' => $it['external_sku_id'] ?? null, 'seller_sku' => $it['seller_sku'] ?? null, 'sku_id' => $it['sku_id'] ?? null,
                'name' => 'Item', 'quantity' => $it['quantity'] ?? 1, 'unit_price' => 50000, 'subtotal' => 50000,
            ]);
        }

        return $order;
    }

    public function test_auto_match_sets_sku_id_and_reserves(): void
    {
        $sku = $this->sku('AO-M', 10);
        $order = $this->channelOrder(StandardOrderStatus::Pending, [['seller_sku' => ' ao-m ', 'external_sku_id' => 'ext-1', 'quantity' => 2]]);
        OrderUpserted::dispatch($order, true);

        $item = OrderItem::withoutGlobalScope(TenantScope::class)->where('order_id', $order->getKey())->first();
        $this->assertSame((int) $sku->getKey(), (int) $item->sku_id);   // auto-matched
        $this->assertSame(2, $this->level($sku)->reserved);
        $this->assertSame(8, $this->level($sku)->available_cached);
        $this->assertFalse($order->fresh()->has_issue);
    }

    public function test_shipping_consumes_stock(): void
    {
        $sku = $this->sku('AO-L', 5);
        $order = $this->channelOrder(StandardOrderStatus::Pending, [['seller_sku' => 'AO-L', 'external_sku_id' => 'ext-2']]);
        OrderUpserted::dispatch($order, true);
        $this->assertSame(1, $this->level($sku)->reserved);

        $order->forceFill(['status' => StandardOrderStatus::Shipped, 'shipped_at' => now()])->save();
        OrderUpserted::dispatch($order, false);
        $level = $this->level($sku);
        $this->assertSame(4, $level->on_hand);
        $this->assertSame(0, $level->reserved);

        // re-fire — idempotent
        OrderUpserted::dispatch($order, false);
        $this->assertSame(4, $this->level($sku)->on_hand);
    }

    public function test_combo_listing_reserves_all_components(): void
    {
        $a = $this->sku('A', 10);
        $b = $this->sku('B', 10);
        $listing = ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->shop->getKey(),
            'external_sku_id' => 'combo-1', 'seller_sku' => 'COMBO-1', 'title' => 'Combo', 'currency' => 'VND',
        ]);
        SkuMapping::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'channel_listing_id' => $listing->getKey(), 'sku_id' => $a->getKey(), 'quantity' => 1, 'type' => 'bundle']);
        SkuMapping::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'channel_listing_id' => $listing->getKey(), 'sku_id' => $b->getKey(), 'quantity' => 2, 'type' => 'bundle']);

        $order = $this->channelOrder(StandardOrderStatus::Processing, [['external_sku_id' => 'combo-1', 'seller_sku' => 'COMBO-1', 'quantity' => 3]]);
        OrderUpserted::dispatch($order, true);

        $this->assertSame(3, $this->level($a)->reserved);    // 1 × 3
        $this->assertSame(6, $this->level($b)->reserved);    // 2 × 3
    }

    public function test_unmapped_item_flags_order_has_issue(): void
    {
        $order = $this->channelOrder(StandardOrderStatus::Pending, [['seller_sku' => 'NOPE-999', 'external_sku_id' => 'ext-x']]);
        OrderUpserted::dispatch($order, true);
        $order->refresh();
        $this->assertTrue($order->has_issue);
        $this->assertSame('SKU chưa ghép', $order->issue_reason);

        // create the SKU + re-fire → resolved, issue cleared
        $sku = $this->sku('NOPE-999', 5);
        OrderUpserted::dispatch($order, false);
        $order->refresh();
        $this->assertFalse($order->has_issue);
        $this->assertSame(1, $this->level($sku)->reserved);
    }

    public function test_cancel_releases_reservation(): void
    {
        $sku = $this->sku('AO-S', 5);
        $order = $this->channelOrder(StandardOrderStatus::Pending, [['seller_sku' => 'AO-S', 'external_sku_id' => 'e1']]);
        OrderUpserted::dispatch($order, true);
        $this->assertSame(1, $this->level($sku)->reserved);

        $order->forceFill(['status' => StandardOrderStatus::Cancelled, 'cancelled_at' => now()])->save();
        OrderUpserted::dispatch($order, false);
        $this->assertSame(0, $this->level($sku)->reserved);
        $this->assertSame(5, $this->level($sku)->on_hand);
    }
}
