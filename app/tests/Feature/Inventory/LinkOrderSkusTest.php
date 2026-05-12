<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class LinkOrderSkusTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Tenant $other;

    private ChannelAccount $shop;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);   // assert push dispatched; don't run it (no TikTok call)
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->other = Tenant::create(['name' => 'Shop B']);
        $this->shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 's1', 'shop_name' => 'S1', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    /** A channel order whose seller_sku doesn't match any master SKU yet ⇒ stays unmapped (has_issue). */
    private function unmappedOrder(string $extId, string $extSku, string $sellerSku, int $qty = 1): Order
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $this->shop->getKey(),
            'external_order_id' => $extId, 'order_number' => $extId, 'status' => StandardOrderStatus::Pending, 'raw_status' => 'X',
            'shipping_address' => [], 'currency' => 'VND', 'grand_total' => 50000, 'item_total' => 50000, 'placed_at' => now(),
            'has_issue' => false, 'tags' => [], 'source_updated_at' => now(),
        ]);
        OrderItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(), 'external_item_id' => 'li-'.$extId,
            'external_sku_id' => $extSku, 'seller_sku' => $sellerSku, 'name' => 'Áo thun M', 'quantity' => $qty, 'unit_price' => 50000, 'subtotal' => 50000,
        ]);
        OrderUpserted::dispatch($order, true);   // runs ApplyOrderInventoryEffects → flags has_issue = "SKU chưa ghép"

        return $order->refresh();
    }

    public function test_unmapped_skus_endpoint_merges_identical_skus_and_suggests(): void
    {
        // orders first (so auto-match doesn't fire — the suggested SKU is created afterwards)
        $o1 = $this->unmappedOrder('O1', 'ext-aom', 'AO-THUN-M', 2);
        $o2 = $this->unmappedOrder('O2', 'ext-aom', 'AO-THUN-M', 1);
        $o3 = $this->unmappedOrder('O3', 'ext-xyz', 'XYZ-999');
        $this->assertTrue($o1->has_issue);
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'ao-thun-m', 'name' => 'Áo M']);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/orders/unmapped-skus')->assertOk();
        $this->assertCount(2, $res->json('data'));   // ext-aom (merged 2 orders) + ext-xyz
        $aom = collect($res->json('data'))->firstWhere('external_sku_id', 'ext-aom');
        $this->assertSame(2, $aom['order_count']);
        $this->assertSame(2, $aom['item_count']);    // 2 order_item rows
        $this->assertSame($sku->getKey(), $aom['suggested_sku_id']);   // 'AO-THUN-M' ~ 'ao-thun-m' (normalized)

        // scoped to one order
        $only = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/orders/unmapped-skus?order_ids={$o3->getKey()}")->assertOk();
        $this->assertCount(1, $only->json('data'));
        $this->assertSame('ext-xyz', $only->json('data.0.external_sku_id'));
    }

    public function test_link_skus_creates_listing_mapping_resolves_orders_and_dispatches_push(): void
    {
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'MASTER-AOM', 'name' => 'Áo M']);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $sku->getKey(), null, 100);
        // seller_sku 'SHOP-AOM-001' does NOT match the master code ⇒ stays unmapped, no auto-match
        $o1 = $this->unmappedOrder('O1', 'ext-aom', 'SHOP-AOM-001', 2);
        $o2 = $this->unmappedOrder('O2', 'ext-aom', 'SHOP-AOM-001', 3);
        $this->assertTrue($o1->has_issue);

        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders/link-skus', [
            'links' => [['channel_account_id' => $this->shop->getKey(), 'external_sku_id' => 'ext-aom', 'seller_sku' => 'SHOP-AOM-001', 'sku_id' => $sku->getKey()]],
        ])->assertOk()->assertJsonPath('data.linked', 1)->assertJsonPath('data.listings_created', 1);

        $listing = ChannelListing::withoutGlobalScope(TenantScope::class)->where('channel_account_id', $this->shop->getKey())->where('external_sku_id', 'ext-aom')->first();
        $this->assertNotNull($listing);
        $this->assertTrue(SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $listing->getKey())->where('sku_id', $sku->getKey())->exists());

        foreach ([$o1, $o2] as $o) {
            $o->refresh();
            $this->assertFalse($o->has_issue);
            $this->assertSame((int) $sku->getKey(), (int) OrderItem::withoutGlobalScope(TenantScope::class)->where('order_id', $o->getKey())->value('sku_id'));
        }
        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $sku->getKey())->first();
        $this->assertSame(5, $level->reserved);
        $this->assertSame(95, $level->available_cached);
        Bus::assertDispatched(PushStockForSku::class, fn ($j) => $j->skuId === (int) $sku->getKey());

        // idempotent: re-running the link doesn't double-reserve
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders/link-skus', [
            'links' => [['channel_account_id' => $this->shop->getKey(), 'external_sku_id' => 'ext-aom', 'seller_sku' => 'SHOP-AOM-001', 'sku_id' => $sku->getKey()]],
        ])->assertOk();
        $this->assertSame(5, $level->fresh()->reserved);
    }

    public function test_link_rejects_other_tenants_sku(): void
    {
        $otherSku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->other->getKey(), 'sku_code' => 'OTH', 'name' => 'oth']);
        $this->unmappedOrder('O1', 'ext-aom', 'SHOP-AOM-001');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders/link-skus', [
            'links' => [['channel_account_id' => $this->shop->getKey(), 'external_sku_id' => 'ext-aom', 'sku_id' => $otherSku->getKey()]],
        ])->assertStatus(422);
    }

    public function test_viewer_cannot_link(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A', 'name' => 'a']);
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/orders/link-skus', [
            'links' => [['channel_account_id' => $this->shop->getKey(), 'external_sku_id' => 'x', 'sku_id' => $sku->getKey()]],
        ])->assertForbidden();
        $this->actingAs($viewer)->withHeaders($this->h())->getJson('/api/v1/orders/unmapped-skus')->assertForbidden();
    }
}
