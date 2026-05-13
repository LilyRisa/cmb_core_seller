<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * SyncOrdersForShop::TYPE_UNPROCESSED — status-based sync iterates over connector's
 * unprocessedRawStatuses() without using a time window. See docs/03-domain/order-sync-pipeline.md §3.3.
 */
class SyncUnprocessedOrdersTest extends TestCase
{
    use RefreshDatabase;

    private ChannelAccount $account;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.lazada.app_key' => 'lzd_key',
            'integrations.lazada.app_secret' => 'lzd_secret',
        ]);
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);

        $this->tenant = Tenant::create(['name' => 'Shop unprocessed test']);
        app(CurrentTenant::class)->set($this->tenant);

        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada',
            'external_shop_id' => 'VNSHOP01',
            'shop_name' => 'Shop Lazada',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'AT',
            'refresh_token' => 'RT',
            'token_expires_at' => now()->addDays(7),
            // last_synced_at = NULL on purpose: muốn chứng minh unprocessed sync vẫn kéo được đơn cũ
            // (kể cả khi shop chưa từng poll thành công lần nào).
            'last_synced_at' => null,
        ]);
    }

    /** @param array<string,mixed> $data */
    private function ok(array $data): array
    {
        return ['code' => '0', 'type' => '', 'request_id' => 'rq', 'data' => $data];
    }

    public function test_unprocessed_sync_iterates_each_status_and_pulls_old_orders(): void
    {
        // Đơn cũ (đặt 6 tháng trước — ngoài cửa sổ poll thường) ở status `pending`.
        // Đơn ở status `ready_to_ship` (đã arrange, chưa bàn giao). Đơn `packed`. Đơn `topack` rỗng.
        Http::fake([
            // /orders/get?status=pending -> 1 đơn cũ
            'https://api.lazada.vn/rest/orders/get*status=pending*' => Http::response($this->ok([
                'count' => 1,
                'orders' => [[
                    'order_id' => 8001, 'order_number' => 'OLD-1',
                    'created_at' => '2025-11-01 10:00:00 +0700', 'updated_at' => '2025-11-01 10:00:00 +0700',
                    'price' => '100000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                    'statuses' => ['pending'],
                    'address_shipping' => ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'city' => 'Hà Nội', 'country' => 'Vietnam'],
                ]],
            ])),
            // /orders/get?status=topack -> rỗng
            'https://api.lazada.vn/rest/orders/get*status=topack*' => Http::response($this->ok(['count' => 0, 'orders' => []])),
            // /orders/get?status=ready_to_ship -> 1 đơn
            'https://api.lazada.vn/rest/orders/get*status=ready_to_ship*' => Http::response($this->ok([
                'count' => 1,
                'orders' => [[
                    'order_id' => 8002, 'order_number' => 'RTS-1',
                    'created_at' => '2026-04-01 10:00:00 +0700', 'updated_at' => '2026-05-10 10:00:00 +0700',
                    'price' => '200000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                    'statuses' => ['ready_to_ship'],
                    'address_shipping' => ['first_name' => 'C', 'last_name' => 'D', 'phone' => '0900000001', 'city' => 'Hồ Chí Minh', 'country' => 'Vietnam'],
                ]],
            ])),
            // /orders/get?status=packed -> 1 đơn
            'https://api.lazada.vn/rest/orders/get*status=packed*' => Http::response($this->ok([
                'count' => 1,
                'orders' => [[
                    'order_id' => 8003, 'order_number' => 'PACKED-1',
                    'created_at' => '2026-05-12 10:00:00 +0700', 'updated_at' => '2026-05-12 10:00:00 +0700',
                    'price' => '300000.00', 'shipping_fee' => '0.00', 'payment_method' => 'COD',
                    'statuses' => ['packed'],
                    'address_shipping' => ['first_name' => 'E', 'last_name' => 'F', 'phone' => '0900000002', 'city' => 'Đà Nẵng', 'country' => 'Vietnam'],
                ]],
            ])),
            // /orders/items/get cho cả 3 đơn (batch)
            '*/orders/items/get*' => Http::response($this->ok([
                ['order_id' => 8001, 'order_items' => [['order_item_id' => 90001, 'sku' => 'A', 'name' => 'A', 'item_price' => 100000, 'status' => 'pending']]],
                ['order_id' => 8002, 'order_items' => [['order_item_id' => 90002, 'sku' => 'B', 'name' => 'B', 'item_price' => 200000, 'status' => 'ready_to_ship']]],
                ['order_id' => 8003, 'order_items' => [['order_item_id' => 90003, 'sku' => 'C', 'name' => 'C', 'item_price' => 300000, 'status' => 'packed']]],
            ])),
        ]);

        // Run job synchronously
        $job = new SyncOrdersForShop((int) $this->account->getKey(), null, SyncRun::TYPE_UNPROCESSED);
        $job->handle(app(ChannelRegistry::class), app(OrderUpsertService::class));

        // Tất cả 3 đơn được upsert
        $this->assertEqualsCanonicalizing(
            ['8001', '8002', '8003'],
            Order::withoutGlobalScope(TenantScope::class)
                ->where('channel_account_id', $this->account->getKey())
                ->pluck('external_order_id')->all(),
        );

        // SyncRun đã DONE, type = unprocessed
        $run = SyncRun::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $this->account->getKey())->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame(SyncRun::TYPE_UNPROCESSED, $run->type);
        $this->assertSame(SyncRun::STATUS_DONE, $run->status);
        $this->assertSame(3, $run->stats['fetched']);

        // Quan trọng: unprocessed KHÔNG bump last_synced_at (giữ NULL như ban đầu)
        $this->account->refresh();
        $this->assertNull($this->account->last_synced_at);

        // Có gọi /orders/get cho cả 4 status (pending/topack/ready_to_ship/packed). update_after có
        // present (vì Lazada bắt buộc update_after hoặc created_after) — nhưng giá trị là 1 năm trước
        // (config `unprocessed_lookback_days` mặc định 365) ⇒ bắt được mọi đơn cũ.
        foreach (['pending', 'topack', 'ready_to_ship', 'packed'] as $st) {
            Http::assertSent(fn ($req) => str_contains($req->url(), '/orders/get')
                && str_contains($req->url(), "status={$st}"));
        }
    }

    public function test_resync_unprocessed_endpoint_dispatches_job(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $this->tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $this->actingAs($owner);

        $resp = $this->withHeader('X-Tenant-Id', (string) $this->tenant->getKey())
            ->postJson("/api/v1/channel-accounts/{$this->account->getKey()}/resync-unprocessed");

        $resp->assertOk()->assertJsonPath('data.queued', true);
        Queue::assertPushed(
            SyncOrdersForShop::class,
            fn (SyncOrdersForShop $job) => $job->channelAccountId === (int) $this->account->getKey()
                && $job->type === SyncRun::TYPE_UNPROCESSED,
        );
    }
}
