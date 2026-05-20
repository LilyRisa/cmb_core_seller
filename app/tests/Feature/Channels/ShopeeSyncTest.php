<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * End-to-end DB feature test: Shopee order-sync pipeline creates Order rows.
 * Mirrors TikTokSyncTest structure. Uses Http::fake + ShopeeFixtures — no real network.
 */
class ShopeeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_orders_from_shopee(): void
    {
        ShopeeFixtures::configure();

        // Register the Shopee connector for this test (mirrors ShopeeConnectorContractTest pattern).
        $registry = app(ChannelRegistry::class);
        $registry->register('shopee', ShopeeConnector::class);

        Http::fake([
            '*/api/v2/order/get_order_list*' => Http::response(ShopeeFixtures::orderList('', false), 200),
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
        ]);

        $tenant = Tenant::create(['name' => 'SP Shop']);

        // Set the current tenant so SyncRun creation (which uses tenant scoping) works.
        app(CurrentTenant::class)->set($tenant);

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => '55',
            'shop_name' => 'Shopee VN',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'ACCESS_1',
            'refresh_token' => 'REFRESH_1',
        ]);

        // Dispatch synchronously — mirrors TikTokSyncTest::test_polling_sync_fetches_and_upserts.
        app(SyncOrdersForShop::class, ['channelAccountId' => (int) $account->getKey()])
            ->handle($registry, app(OrderUpsertService::class));

        // Both orders from ShopeeFixtures::orderDetail() must be created.
        $this->assertCount(2, Order::withoutGlobalScope(TenantScope::class)
            ->whereIn('external_order_id', ['SN_1', 'SN_2'])->get());

        // SN_1 has READY_TO_SHIP -> mapped to 'pending' per the status_map.
        $order = Order::withoutGlobalScope(TenantScope::class)
            ->where('external_order_id', 'SN_1')
            ->first();
        $this->assertNotNull($order);
        $this->assertSame('shopee', $order->source);
        $this->assertSame(StandardOrderStatus::Pending, $order->status); // READY_TO_SHIP -> pending
        $this->assertSame((int) $account->getKey(), (int) $order->channel_account_id);

        // SN_2 has PROCESSED -> mapped to 'processing'.
        $order2 = Order::withoutGlobalScope(TenantScope::class)
            ->where('external_order_id', 'SN_2')
            ->first();
        $this->assertNotNull($order2);
        $this->assertSame(StandardOrderStatus::Processing, $order2->status);

        // SyncRun should be marked done with correct stats.
        $run = SyncRun::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $account->getKey())
            ->first();
        $this->assertNotNull($run);
        $this->assertSame(SyncRun::STATUS_DONE, $run->status);
        $this->assertSame(2, $run->stats['fetched']);
        $this->assertSame(2, $run->stats['created']);

        // last_synced_at watermark must be bumped.
        $this->assertNotNull($account->fresh()->last_synced_at);
    }
}
