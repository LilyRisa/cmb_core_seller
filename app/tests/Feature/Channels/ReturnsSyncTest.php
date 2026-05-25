<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Jobs\SyncReturnsForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Orders\Contracts\ReturnUpsertContract;
use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/** Đồng bộ Hoàn & Hủy (after-sales) cho TikTok — poll job + webhook trigger. SPEC 0025. */
class ReturnsSyncTest extends TestCase
{
    use RefreshDatabase;

    private ChannelAccount $account;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();
        config(['integrations.tiktok.returns_enabled' => true]);
        $this->tenant = Tenant::create(['name' => 'Shop returns sync']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => F::SHOP_ID,
            'shop_name' => 'X', 'status' => ChannelAccount::STATUS_ACTIVE, 'access_token' => 'tk',
            'meta' => ['shop_cipher' => F::SHOP_CIPHER],
        ]);
    }

    private function runJob(): void
    {
        app(SyncReturnsForShop::class, ['channelAccountId' => (int) $this->account->getKey()])
            ->handle(app(ChannelRegistry::class), app(ReturnUpsertContract::class), app(TokenRefresher::class));
    }

    public function test_poll_syncs_returns_and_cancellations(): void
    {
        Http::fake([
            '*/return_refund/202309/returns/search*' => Http::response(F::returnsSearch()),
            '*/return_refund/202309/cancellations/search*' => Http::response(F::cancellationsSearch()),
        ]);

        $this->runJob();

        $rows = OrderReturn::withoutGlobalScope(TenantScope::class)->get();
        $this->assertCount(2, $rows);
        $ret = $rows->firstWhere('external_return_id', 'RET-1');
        $this->assertSame('return', $ret->kind);
        $this->assertSame(AfterSalesStatus::Requested, $ret->status);
        $this->assertSame(50000, $ret->refund_amount);
        $cxl = $rows->firstWhere('external_return_id', 'CXL-1');
        $this->assertSame('cancel', $cxl->kind);
    }

    public function test_poll_is_idempotent(): void
    {
        Http::fake([
            '*/return_refund/202309/returns/search*' => Http::response(F::returnsSearch()),
            '*/return_refund/202309/cancellations/search*' => Http::response(F::cancellationsSearch()),
        ]);

        $this->runJob();
        $this->runJob();

        $this->assertSame(2, OrderReturn::withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_return_webhook_dispatches_returns_sync(): void
    {
        Queue::fake();
        // tạo & xử lý 1 webhook return_update (type 13) — ProcessWebhookEvent → dispatch SyncReturnsForShop.
        $event = WebhookEvent::create([
            'provider' => 'tiktok', 'event_type' => 'return_update', 'external_id' => 'RET-1',
            'external_shop_id' => F::SHOP_ID, 'signature_ok' => true, 'payload' => ['data' => ['return_id' => 'RET-1']], 'received_at' => now(),
        ]);

        app(ProcessWebhookEvent::class, ['webhookEventId' => $event->id])->handle(
            app(ChannelRegistry::class), app(OrderUpsertService::class), app(TokenRefresher::class),
        );

        Queue::assertPushed(SyncReturnsForShop::class, fn (SyncReturnsForShop $j) => $j->channelAccountId === (int) $this->account->getKey());
    }
}
