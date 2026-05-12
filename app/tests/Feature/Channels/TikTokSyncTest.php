<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountNeedsReconnect;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Http::fake(['*/order/202309/orders?*' => Http::response(F::orderDetail(F::ORDER_ID, 'AWAITING_COLLECTION'))]);

        $event = WebhookEvent::create([
            'provider' => 'tiktok', 'event_type' => 'order_status_update',
            'external_id' => F::ORDER_ID, 'external_shop_id' => F::SHOP_ID,
            'signature_ok' => true, 'payload' => F::webhookOrderStatusChange()['body'], 'received_at' => now(),
        ]);

        $this->processWebhook($event);

        $order = $this->order();
        $this->assertNotNull($order);
        $this->assertSame('tiktok', $order->source);
        $this->assertSame(StandardOrderStatus::ReadyToShip, $order->status); // AWAITING_COLLECTION -> ready_to_ship
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
            'external_order_id' => F::ORDER_ID, 'order_number' => F::ORDER_ID, 'status' => StandardOrderStatus::Processing, 'raw_status' => 'AWAITING_SHIPMENT',
            'shipping_address' => [], 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => now()->subDay(),
            'has_issue' => false, 'tags' => [], 'source_updated_at' => now()->subHour(),
        ]);
        // Re-fetch fails (TikTok returns a non-zero code), but the push carried the new status.
        Http::fake(['*/order/202309/orders?*' => Http::response(['code' => 5000, 'message' => 'temporary error', 'data' => [], 'request_id' => 'r'], 200)]);

        $event = WebhookEvent::create([
            'provider' => 'tiktok', 'event_type' => 'order_status_update', 'external_id' => F::ORDER_ID, 'external_shop_id' => F::SHOP_ID,
            'order_raw_status' => 'AWAITING_COLLECTION', 'signature_ok' => true,
            'payload' => ['type' => 1, 'shop_id' => F::SHOP_ID, 'data' => ['order_id' => F::ORDER_ID, 'order_status' => 'AWAITING_COLLECTION', 'update_time' => now()->timestamp]],
            'received_at' => now(),
        ]);

        $this->processWebhook($event);

        $order->refresh();
        $this->assertSame(StandardOrderStatus::ReadyToShip, $order->status);   // applied from the webhook payload
        $this->assertSame('AWAITING_COLLECTION', $order->raw_status);
        $this->assertSame(1, $order->statusHistory()->count());
        $this->assertSame('webhook', $order->statusHistory()->first()->source);
        // source_updated_at NOT bumped, so a later full re-fetch can still enrich it.
        $this->assertTrue($order->source_updated_at->lt(now()->subMinutes(30)));
        $this->assertSame('processed', $event->fresh()->status);
    }

    public function test_status_change_records_a_new_history_row(): void
    {
        Http::fake(['*/order/202309/orders?*' => Http::sequence()
            ->push(F::orderDetail(F::ORDER_ID, 'AWAITING_SHIPMENT', updateTime: now()->subMinutes(20)->timestamp))
            ->push(F::orderDetail(F::ORDER_ID, 'IN_TRANSIT', updateTime: now()->subMinutes(5)->timestamp))]);

        $e1 = WebhookEvent::create(['provider' => 'tiktok', 'event_type' => 'order_status_update', 'external_id' => F::ORDER_ID, 'external_shop_id' => F::SHOP_ID, 'signature_ok' => true, 'payload' => [], 'received_at' => now()]);
        $this->processWebhook($e1);
        // AWAITING_SHIPMENT + already has a package -> processing (see TikTokStatusMap / order-status-state-machine §4)
        $this->assertSame(StandardOrderStatus::Processing, $this->order()->status);

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
        $this->assertNotNull($this->account->fresh()->last_synced_at);
        $run = SyncRun::withoutGlobalScope(TenantScope::class)->first();
        $this->assertSame('done', $run->status);
        $this->assertSame(2, $run->stats['fetched']);
        $this->assertSame(2, $run->stats['created']);
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
}
