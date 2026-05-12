<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\FetchChannelListings;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockToListing;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Services\SkuMappingService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

class FetchChannelListingsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();
        Bus::fake([PushStockToListing::class]);   // don't fire the auto-push (TikTok inventory/update) — assert separately if needed
        $this->tenant = Tenant::create(['name' => 'Listings test']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => F::SHOP_ID,
            'shop_name' => 'Shop', 'status' => ChannelAccount::STATUS_ACTIVE, 'access_token' => 'tk', 'meta' => ['shop_cipher' => F::SHOP_CIPHER],
        ]);
    }

    private function runJob(): void
    {
        app(FetchChannelListings::class, ['channelAccountId' => (int) $this->account->getKey()])
            ->handle(app(ChannelRegistry::class), app(SkuMappingService::class), app(TokenRefresher::class));
    }

    public function test_tiktok_supports_listings_fetch(): void
    {
        $this->assertTrue(app(ChannelRegistry::class)->for('tiktok')->supports('listings.fetch'));
    }

    public function test_fetches_listings_upserts_and_auto_matches_existing_sku(): void
    {
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'ao-thun-m', 'name' => 'Áo M']);
        Http::fake(['*/product/202309/products/search*' => Http::response(F::productsSearch())]);

        $this->runJob();

        $listing = ChannelListing::withoutGlobalScope(TenantScope::class)->where('external_sku_id', F::SKU_ID)->first();
        $this->assertNotNull($listing);
        $this->assertSame((int) $this->account->getKey(), (int) $listing->channel_account_id);
        $this->assertSame(F::PRODUCT_ID, $listing->external_product_id);
        $this->assertSame('AO-THUN-M', $listing->seller_sku);
        $this->assertSame(12, $listing->channel_stock);
        $this->assertSame(199000, $listing->price);
        $this->assertStringContainsString('Màu: Trắng', (string) $listing->variation);
        $this->assertStringContainsString('Size: M', (string) $listing->variation);
        $this->assertTrue($listing->is_active);
        $this->assertNotNull($listing->last_fetched_at);

        // auto-matched: seller_sku 'AO-THUN-M' == sku_code 'ao-thun-m' (normalized)
        $this->assertTrue(SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $listing->getKey())->where('sku_id', $sku->getKey())->exists());

        // re-run → idempotent
        $this->runJob();
        $this->assertSame(1, ChannelListing::withoutGlobalScope(TenantScope::class)->count());
        $this->assertSame(1, SkuMapping::withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_listing_kept_unmapped_when_no_matching_sku(): void
    {
        Http::fake(['*/product/202309/products/search*' => Http::response(F::productsSearch())]);
        $this->runJob();
        $listing = ChannelListing::withoutGlobalScope(TenantScope::class)->first();
        $this->assertNotNull($listing);
        $this->assertSame(0, SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $listing->getKey())->count());
    }
}
