<?php

namespace Tests\Feature\Channels;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokWebhookVerifier;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountNeedsReconnect;
use CMBcoreSeller\Modules\Channels\Jobs\FetchChannelListings;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Inventory\Services\SkuMappingService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/**
 * The pull+push order-sync pipeline for TikTok, end to end (no real network).
 * See docs/03-domain/order-sync-pipeline.md, SPEC 0001.
 */
class TikTokSyncTest extends TestCase
{
    use RefreshDatabase;

    private ChannelAccount $account;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();

        $this->tenant = Tenant::create(['name' => 'Shop sync test']);
        // Outside an HTTP request there's no `tenant` middleware — set it so the
        // global scope resolves to this tenant for direct model queries below.
        app(CurrentTenant::class)->set($this->tenant);

        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'tiktok',
            'external_shop_id' => F::SHOP_ID,
            'shop_name' => 'Cửa hàng test',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tk_access',
            'refresh_token' => 'tk_refresh',
            'token_expires_at' => now()->addDays(7),
            'meta' => ['shop_cipher' => F::SHOP_CIPHER],
        ]);
    }

    private function processWebhook(WebhookEvent $event): void
    {
        app(ProcessWebhookEvent::class, ['webhookEventId' => $event->id])->handle(
            app(ChannelRegistry::class), app(OrderUpsertService::class), app(TokenRefresher::class),
        );
    }

    private function order(string $extId = F::ORDER_ID): ?Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->where('external_order_id', $extId)->first();
    }

    public function test_webhook_endpoint_verifies_stores_and_dispatches(): void
    {
        Queue::fake();
        $wh = F::webhookOrderStatusChange();

        // bad signature -> 401, nothing stored
        $this->postJson('/webhook/tiktok', $wh['body'], ['Authorization' => 'wrong'])->assertStatus(401);
        $this->assertSame(0, WebhookEvent::count());

        // valid signature -> 200, event stored, ProcessWebhookEvent queued
        $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $wh['signature'], 'CONTENT_TYPE' => 'application/json'], content: $wh['raw'])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(1, WebhookEvent::count());
        $event = WebhookEvent::first();
        $this->assertTrue($event->signature_ok);
        $this->assertSame(F::ORDER_ID, $event->external_id);
        Queue::assertPushed(ProcessWebhookEvent::class);

        // a duplicate is acked but not re-stored once the first one is processed
        $event->markProcessed();
        $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $wh['signature'], 'CONTENT_TYPE' => 'application/json'], content: $wh['raw'])->assertOk();
        $this->assertSame(1, WebhookEvent::count());
    }

    public function test_process_webhook_event_refetches_order_and_upserts_idempotently(): void
    {
        Http::fake(['*/order/202507/orders?*' => Http::response(F::orderDetail(F::ORDER_ID, 'AWAITING_COLLECTION'))]);

        $event = WebhookEvent::create([
            'provider' => 'tiktok', 'event_type' => 'order_status_update',
            'external_id' => F::ORDER_ID, 'external_shop_id' => F::SHOP_ID,
            'signature_ok' => true, 'payload' => F::webhookOrderStatusChange()['body'], 'received_at' => now(),
        ]);

        $this->processWebhook($event);

        $order = $this->order();
        $this->assertNotNull($order);
        $this->assertSame('tiktok', $order->source);
        $this->assertSame(StandardOrderStatus::Processing, $order->status); // SPEC 0013: AWAITING_COLLECTION (đã in phiếu) -> processing
        $this->assertSame((int) $this->account->getKey(), (int) $order->channel_account_id);
        $this->assertSame(205000, $order->grand_total);
        $this->assertSame(2, $order->items()->sum('quantity'));
        $this->assertSame(1, $order->statusHistory()->count());
        $this->assertSame('processed', $event->fresh()->status);

        // re-run with the same payload (same source_updated_at) -> idempotent no-op
        $this->processWebhook($event);
        $this->assertSame(1, $order->fresh()->statusHistory()->count());
        $this->assertSame(1, $order->fresh()->items()->count());
    }

    public function test_webhook_applies_status_from_payload_when_refetch_fails(): void
    {
        // Existing order, last synced a while ago.
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $this->account->getKey(),
            'external_order_id' => F::ORDER_ID, 'order_number' => F::ORDER_ID, 'status' => StandardOrderStatus::Pending, 'raw_status' => 'AWAITING_SHIPMENT',
            'shipping_address' => [], 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => now()->subDay(),
            'has_issue' => false, 'tags' => [], 'source_updated_at' => now()->subHour(),
        ]);
        // Re-fetch fails (TikTok returns a non-zero code), but the push carried the new status.
        Http::fake(['*/order/202507/orders?*' => Http::response(['code' => 5000, 'message' => 'temporary error', 'data' => [], 'request_id' => 'r'], 200)]);

        $event = WebhookEvent::create([
            'provider' => 'tiktok', 'event_type' => 'order_status_update', 'external_id' => F::ORDER_ID, 'external_shop_id' => F::SHOP_ID,
            'order_raw_status' => 'AWAITING_COLLECTION', 'signature_ok' => true,
            'payload' => ['type' => 1, 'shop_id' => F::SHOP_ID, 'data' => ['order_id' => F::ORDER_ID, 'order_status' => 'AWAITING_COLLECTION', 'update_time' => now()->timestamp]],
            'received_at' => now(),
        ]);

        $this->processWebhook($event);

        $order->refresh();
        $this->assertSame(StandardOrderStatus::Processing, $order->status);   // applied from the webhook payload (AWAITING_COLLECTION -> processing, SPEC 0013)
        $this->assertSame('AWAITING_COLLECTION', $order->raw_status);
        $this->assertSame(1, $order->statusHistory()->count());
        $this->assertSame('webhook', $order->statusHistory()->first()->source);
        // source_updated_at NOT bumped, so a later full re-fetch can still enrich it.
        $this->assertTrue($order->source_updated_at->lt(now()->subMinutes(30)));
        $this->assertSame('processed', $event->fresh()->status);
    }

    public function test_status_change_records_a_new_history_row(): void
    {
        Http::fake(['*/order/202507/orders?*' => Http::sequence()
            ->push(F::orderDetail(F::ORDER_ID, 'AWAITING_SHIPMENT', updateTime: now()->subMinutes(20)->timestamp))
            ->push(F::orderDetail(F::ORDER_ID, 'IN_TRANSIT', updateTime: now()->subMinutes(5)->timestamp))]);

        $e1 = WebhookEvent::create(['provider' => 'tiktok', 'event_type' => 'order_status_update', 'external_id' => F::ORDER_ID, 'external_shop_id' => F::SHOP_ID, 'signature_ok' => true, 'payload' => [], 'received_at' => now()]);
        $this->processWebhook($e1);
        // SPEC 0013: AWAITING_SHIPMENT (chưa in/arrange phiếu) -> pending (kể cả khi đã có package)
        $this->assertSame(StandardOrderStatus::Pending, $this->order()->status);

        $e2 = WebhookEvent::create(['provider' => 'tiktok', 'event_type' => 'order_status_update', 'external_id' => F::ORDER_ID.':2', 'external_shop_id' => F::SHOP_ID, 'signature_ok' => true, 'payload' => ['data' => ['order_id' => F::ORDER_ID]], 'received_at' => now()]);
        $this->processWebhook($e2);

        $order = $this->order();
        $this->assertSame(StandardOrderStatus::Shipped, $order->status);
        $this->assertSame(2, $order->statusHistory()->count());
        $this->assertSame('webhook', $order->statusHistory()->first()->source);
        $this->assertSame('shipped', $order->statusHistory()->first()->to_status);
    }

    public function test_polling_sync_fetches_and_upserts(): void
    {
        Http::fake(['*/order/202309/orders/search*' => Http::response(F::ordersSearch([F::order('ORD-1', 'AWAITING_SHIPMENT'), F::order('ORD-2', 'IN_TRANSIT')]))]);

        app(SyncOrdersForShop::class, ['channelAccountId' => (int) $this->account->getKey()])->handle(app(ChannelRegistry::class), app(OrderUpsertService::class));

        $this->assertCount(2, Order::withoutGlobalScope(TenantScope::class)->whereIn('external_order_id', ['ORD-1', 'ORD-2'])->get());
        // Phân trang chạy hết (1 trang) ⇒ watermark nhảy lên gần "bây giờ" (runStart - overlap).
        $watermark = $this->account->fresh()->last_synced_at;
        $this->assertNotNull($watermark);
        $this->assertTrue(CarbonImmutable::parse($watermark)->gt(now()->subMinutes(30)));
        $run = SyncRun::withoutGlobalScope(TenantScope::class)->first();
        $this->assertSame('done', $run->status);
        $this->assertSame(2, $run->stats['fetched']);
        $this->assertSame(2, $run->stats['created']);
    }

    public function test_fetch_listings_overlays_active_promotion_into_special_price(): void
    {
        // products/search: 2 SKU (S1 đang KM, S2 không). activities: 1 chương trình FIXED_PRICE chứa S1@520000.
        Http::fake([
            '*/product/*/products/search*' => Http::response(['code' => 0, 'data' => ['products' => [
                ['id' => 'P1', 'title' => 'SP1', 'status' => 'ACTIVATE', 'main_images' => [['thumb_urls' => ['http://img/1']]],
                    'skus' => [['id' => 'S1', 'seller_sku' => 'SK1', 'price' => ['currency' => 'VND', 'sale_price' => '650000'], 'inventory' => [['quantity' => 5]]]]],
                ['id' => 'P2', 'title' => 'SP2', 'status' => 'ACTIVATE', 'main_images' => [['thumb_urls' => ['http://img/2']]],
                    'skus' => [['id' => 'S2', 'seller_sku' => 'SK2', 'price' => ['currency' => 'VND', 'sale_price' => '100000'], 'inventory' => [['quantity' => 9]]]]],
            ], 'next_page_token' => '']]),
            '*/promotion/202309/activities/search*' => Http::response(['code' => 0, 'data' => ['activities' => [['id' => 'ACT1', 'status' => 'ONGOING']]]]),
            '*/promotion/202309/activities/*' => Http::response(['code' => 0, 'data' => [
                'activity_id' => 'ACT1', 'status' => 'ONGOING', 'title' => 'KM', 'product_level' => 'VARIATION', 'activity_type' => 'FIXED_PRICE',
                'products' => [['id' => 'P1', 'skus' => [['id' => 'S1', 'activity_price' => ['amount' => '520000', 'currency' => 'VND']]]]],
            ]]),
        ]);

        app(FetchChannelListings::class, ['channelAccountId' => (int) $this->account->getKey()])
            ->handle(app(ChannelRegistry::class), app(SkuMappingService::class), app(TokenRefresher::class));

        $s1 = ChannelListing::withoutGlobalScope(TenantScope::class)->where('external_sku_id', 'S1')->first();
        $s2 = ChannelListing::withoutGlobalScope(TenantScope::class)->where('external_sku_id', 'S2')->first();
        $this->assertNotNull($s1);
        $this->assertSame(650000, (int) $s1->price, 'Giá thường = sale_price từ listing.');
        $this->assertSame(520000, (int) $s1->special_price, 'SKU đang KM ⇒ special_price = giá hoạt động.');
        $this->assertNull($s2->special_price, 'SKU KHÔNG KM ⇒ special_price null.');
    }

    public function test_token_refresh_updates_token_then_marks_expired_on_failure(): void
    {
        Event::fake([ChannelAccountNeedsReconnect::class]);
        // first refresh succeeds, second fails (code != 0) — one fake, a sequence
        Http::fake(['*/api/v2/token/refresh*' => Http::sequence()
            ->push(F::tokenRefresh())
            ->push(['code' => 105001, 'message' => 'invalid refresh token'], 200)]);

        $this->assertTrue(app(TokenRefresher::class)->refresh($this->account));
        $this->account->refresh();
        $this->assertSame('tk_access_NEW', $this->account->access_token);
        $this->assertSame('tk_refresh_NEW', $this->account->refresh_token);
        $this->assertSame(ChannelAccount::STATUS_ACTIVE, $this->account->status);

        $this->assertFalse(app(TokenRefresher::class)->refresh($this->account));
        $this->assertSame(ChannelAccount::STATUS_EXPIRED, $this->account->fresh()->status);
        Event::assertDispatched(ChannelAccountNeedsReconnect::class);
    }

    public function test_transient_refresh_failure_keeps_account_active(): void
    {
        // A transient failure (5xx / network blip) must NOT expire the shop while the refresh token is still
        // valid — the current access token keeps sync alive and the next scheduled run retries. This is what
        // stopped Shopee shops from "dropping after a few hours" on a single refresh hiccup.
        Event::fake([ChannelAccountNeedsReconnect::class]);
        $this->account->forceFill(['refresh_token_expires_at' => now()->addDays(20)])->save();
        Http::fake(['*/api/v2/token/refresh*' => Http::response(['message' => 'internal error'], 500)]);

        $this->assertFalse(app(TokenRefresher::class)->refresh($this->account));
        $this->assertSame(ChannelAccount::STATUS_ACTIVE, $this->account->fresh()->status);
        Event::assertNotDispatched(ChannelAccountNeedsReconnect::class);
    }

    /** Build a signed TikTok webhook body for a given order status. */
    private function signedWebhook(string $status, string $secret = F::APP_SECRET, string $orderId = F::ORDER_ID): array
    {
        $body = [
            'type' => 1, 'tts_notification_id' => 'ntf-'.uniqid(), 'shop_id' => F::SHOP_ID, 'timestamp' => now()->timestamp,
            'data' => ['order_id' => $orderId, 'order_status' => $status, 'update_time' => now()->timestamp],
        ];
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return ['raw' => $raw, 'sig' => hash_hmac('sha256', F::APP_KEY.$raw, $secret)];
    }

    public function test_webhook_dedup_keeps_distinct_status_changes(): void
    {
        Queue::fake();

        // First push (AWAITING_SHIPMENT) stored + processed.
        $a = $this->signedWebhook('AWAITING_SHIPMENT');
        $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $a['sig'], 'CONTENT_TYPE' => 'application/json'], content: $a['raw'])->assertOk();
        $this->assertSame(1, WebhookEvent::count());
        WebhookEvent::query()->update(['status' => WebhookEvent::STATUS_PROCESSED]);

        // A DIFFERENT status for the SAME order must NOT be deduped away.
        $b = $this->signedWebhook('AWAITING_COLLECTION');
        $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $b['sig'], 'CONTENT_TYPE' => 'application/json'], content: $b['raw'])->assertOk();
        $this->assertSame(2, WebhookEvent::count());

        // A retry of the SAME status (different notification id) is still deduped.
        $b2 = $this->signedWebhook('AWAITING_COLLECTION');
        $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $b2['sig'], 'CONTENT_TYPE' => 'application/json'], content: $b2['raw'])->assertOk();
        $this->assertSame(2, WebhookEvent::count());
    }

    public function test_webhook_verifier_uses_rotated_secret_from_system_setting(): void
    {
        // Super-admin đổi secret nóng qua /admin/settings (lưu DB). Verifier PHẢI dùng secret động
        // (system_setting) như TikTokClient — nếu chỉ đọc config tĩnh thì mọi webhook bị 401.
        app(SystemSettingService::class)->set('marketplace.tiktok.app_secret', 'rotated_secret_xyz');

        $wh = $this->signedWebhook('AWAITING_COLLECTION', secret: 'rotated_secret_xyz');
        $request = Request::create('/webhook/tiktok', 'POST', server: ['HTTP_AUTHORIZATION' => $wh['sig']], content: $wh['raw']);

        $this->assertTrue((new TikTokWebhookVerifier)->verify($request));
    }

    public function test_poll_resumes_watermark_when_page_cap_hit(): void
    {
        config(['integrations.sync.poll_max_pages' => 2]);
        // Mỗi trang trả 1 đơn (update ~1h trước) và LUÔN có next_page_token ⇒ chạm trần 2 trang vẫn còn dữ liệu.
        $updatedTs = now()->subHour()->timestamp;
        Http::fake(['*/order/202309/orders/search*' => function () use ($updatedTs) {
            return Http::response(F::ordersSearch([F::order('ORD-'.uniqid(), 'AWAITING_SHIPMENT', $updatedTs)], nextToken: 'NEXT'));
        }]);

        app(SyncOrdersForShop::class, ['channelAccountId' => (int) $this->account->getKey()])
            ->handle(app(ChannelRegistry::class), app(OrderUpsertService::class));

        $watermark = $this->account->fresh()->last_synced_at;
        $this->assertNotNull($watermark);
        // KHÔNG nhảy lên gần "bây giờ": resume từ update_time đơn đã xử lý (~1h trước) - overlap.
        $this->assertTrue(CarbonImmutable::parse($watermark)->lt(now()->subMinutes(30)));
    }
}
