<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockToListing;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

class PushStockTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $shop;

    private Sku $sku;

    private ChannelListing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();   // test app_key/app_secret so the TikTok client can sign
        Bus::fake();      // don't auto-run pushed jobs; assert/run them explicitly
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 's1', 'shop_name' => 'S1', 'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tok', 'meta' => ['shop_cipher' => 'cipher'],
        ]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'AO-M', 'name' => 'Áo M']);
        $this->listing = ChannelListing::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->shop->getKey(),
            'external_product_id' => 'prod-1', 'external_sku_id' => 'sku-ext-1', 'seller_sku' => 'AO-M', 'title' => 'L', 'currency' => 'VND', 'channel_stock' => 0,
        ]);
        SkuMapping::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'channel_listing_id' => $this->listing->getKey(), 'sku_id' => $this->sku->getKey(), 'quantity' => 1, 'type' => 'single']);
    }

    public function test_stock_change_schedules_a_push(): void
    {
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 7);
        Bus::assertDispatched(PushStockForSku::class, fn ($j) => $j->tenantId === (int) $this->tenant->getKey() && $j->skuId === (int) $this->sku->getKey());
    }

    public function test_push_for_sku_dispatches_per_listing_only_when_value_differs(): void
    {
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 9);  // available 9, channel_stock 0 ⇒ differs

        (new PushStockForSku((int) $this->tenant->getKey(), (int) $this->sku->getKey()))->handle(app(InventoryLedgerService::class));
        Bus::assertDispatched(PushStockToListing::class, fn ($j) => $j->channelListingId === (int) $this->listing->getKey() && $j->desired === 9);

        // when channel_stock already matches → no dispatch
        $this->listing->forceFill(['channel_stock' => 9])->save();
        Bus::fake();
        (new PushStockForSku((int) $this->tenant->getKey(), (int) $this->sku->getKey()))->handle(app(InventoryLedgerService::class));
        Bus::assertNotDispatched(PushStockToListing::class);
    }

    public function test_push_to_listing_calls_tiktok_update_stock_and_records_result(): void
    {
        Http::fake(['*' => Http::response(['code' => 0, 'message' => 'ok', 'data' => [], 'request_id' => 'r'], 200)]);

        (new PushStockToListing((int) $this->listing->getKey(), 5))->handle(app(ChannelRegistry::class));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'prod-1') && str_contains($request->url(), 'inventory/update'));
        $this->listing->refresh();
        $this->assertSame(5, $this->listing->channel_stock);
        $this->assertSame(ChannelListing::SYNC_OK, $this->listing->sync_status);
        $this->assertNotNull($this->listing->last_pushed_at);
    }

    public function test_push_to_listing_is_noop_when_locked(): void
    {
        Http::fake();
        $this->listing->forceFill(['is_stock_locked' => true])->save();
        (new PushStockToListing((int) $this->listing->getKey(), 3))->handle(app(ChannelRegistry::class));
        Http::assertNothingSent();
        $this->assertNull($this->listing->fresh()->last_pushed_at);
    }
}
