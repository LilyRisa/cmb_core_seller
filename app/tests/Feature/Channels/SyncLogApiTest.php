<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SyncLogApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Tenant $otherTenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'My shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->otherTenant = Tenant::create(['name' => 'Someone else']);

        $this->account = $this->makeAccount($this->tenant);
        $otherAccount = $this->makeAccount($this->otherTenant);

        SyncRun::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->getKey(),
            'type' => SyncRun::TYPE_POLL, 'status' => SyncRun::STATUS_DONE,
            'started_at' => now()->subMinutes(5), 'finished_at' => now()->subMinutes(4),
            'stats' => ['fetched' => 3, 'created' => 1, 'updated' => 2, 'skipped' => 0, 'errors' => 0],
        ]);
        SyncRun::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->getKey(),
            'type' => SyncRun::TYPE_BACKFILL, 'status' => SyncRun::STATUS_FAILED,
            'started_at' => now()->subMinutes(2), 'finished_at' => now()->subMinutes(1), 'error' => 'boom',
            'stats' => ['fetched' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 1],
        ]);
        // a run for another tenant — must not leak
        SyncRun::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->otherTenant->getKey(), 'channel_account_id' => $otherAccount->getKey(),
            'type' => SyncRun::TYPE_POLL, 'status' => SyncRun::STATUS_DONE, 'started_at' => now(),
            'stats' => ['fetched' => 1],
        ]);

        WebhookEvent::create([
            'provider' => 'tiktok', 'event_type' => 'order_status_update', 'external_id' => 'TT-1',
            'external_shop_id' => $this->account->external_shop_id, 'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->getKey(), 'signature_ok' => true, 'status' => WebhookEvent::STATUS_FAILED,
            'attempts' => 5, 'error' => 'fetch failed', 'payload' => ['data' => ['order_id' => 'TT-1']], 'headers' => [],
            'received_at' => now()->subMinutes(3),
        ]);
        WebhookEvent::create([
            'provider' => 'tiktok', 'event_type' => 'order_created', 'external_id' => 'TT-2',
            'external_shop_id' => $otherAccount->external_shop_id, 'tenant_id' => $this->otherTenant->getKey(),
            'channel_account_id' => $otherAccount->getKey(), 'signature_ok' => true, 'status' => WebhookEvent::STATUS_PROCESSED,
            'attempts' => 1, 'payload' => [], 'headers' => [], 'received_at' => now(),
        ]);
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeAccount(Tenant $tenant): ChannelAccount
    {
        return ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'tiktok',
            'external_shop_id' => 'shop-'.$tenant->getKey(), 'shop_name' => 'Shop '.$tenant->getKey(),
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    public function test_sync_runs_listing_is_tenant_scoped_and_filterable(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->header())->getJson('/api/v1/sync-runs')->assertOk();
        $res->assertJsonCount(2, 'data')->assertJsonPath('meta.pagination.total', 2);
        $this->assertSame('Shop '.$this->tenant->getKey(), $res->json('data.0.shop_name'));

        $this->actingAs($this->owner)->withHeaders($this->header())->getJson('/api/v1/sync-runs?status=failed')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 'backfill');
    }

    public function test_webhook_events_listing_is_tenant_scoped_and_hides_payload(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->header())->getJson('/api/v1/webhook-events')->assertOk();
        $res->assertJsonCount(1, 'data')->assertJsonPath('data.0.external_id', 'TT-1');
        $this->assertArrayNotHasKey('payload', $res->json('data.0'));
        $this->assertArrayNotHasKey('headers', $res->json('data.0'));

        $this->actingAs($this->owner)->withHeaders($this->header())->getJson('/api/v1/webhook-events?status=processed')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_redrive_webhook_resets_and_dispatches(): void
    {
        Bus::fake();
        $event = WebhookEvent::where('external_id', 'TT-1')->first();

        $this->actingAs($this->owner)->withHeaders($this->header())
            ->postJson("/api/v1/webhook-events/{$event->getKey()}/redrive")
            ->assertOk()->assertJsonPath('data.queued', true);

        Bus::assertDispatched(ProcessWebhookEvent::class, fn ($j) => $j->webhookEventId === $event->getKey());
        $this->assertSame(WebhookEvent::STATUS_PENDING, $event->fresh()->status);
        $this->assertNull($event->fresh()->error);
    }

    public function test_redrive_sync_run_dispatches_sync_job(): void
    {
        Bus::fake();
        $run = SyncRun::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->where('type', SyncRun::TYPE_BACKFILL)->first();

        $this->actingAs($this->owner)->withHeaders($this->header())
            ->postJson("/api/v1/sync-runs/{$run->getKey()}/redrive")
            ->assertOk()->assertJsonPath('data.type', 'backfill');

        Bus::assertDispatched(SyncOrdersForShop::class, fn ($j) => $j->channelAccountId === $this->account->getKey() && $j->type === SyncRun::TYPE_BACKFILL);
    }

    public function test_viewer_can_list_but_not_redrive(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $event = WebhookEvent::where('external_id', 'TT-1')->first();

        $this->actingAs($viewer)->withHeaders($this->header())->getJson('/api/v1/sync-runs')->assertOk();
        $this->actingAs($viewer)->withHeaders($this->header())->postJson("/api/v1/webhook-events/{$event->getKey()}/redrive")->assertForbidden();
    }

    public function test_cannot_redrive_another_tenants_webhook(): void
    {
        $other = WebhookEvent::where('external_id', 'TT-2')->first();
        $this->actingAs($this->owner)->withHeaders($this->header())
            ->postJson("/api/v1/webhook-events/{$other->getKey()}/redrive")->assertNotFound();
    }
}
