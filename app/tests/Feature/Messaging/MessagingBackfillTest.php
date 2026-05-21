<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagingBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_add_sync_and_block_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('messaging_account_meta', [
            'page_avatar_path', 'page_avatar_synced_at', 'sync_status',
            'sync_total_conversations', 'sync_done_conversations', 'sync_message_count',
            'sync_cursor', 'sync_started_at', 'sync_finished_at', 'sync_error', 'last_synced_at',
        ]));
        $this->assertTrue(Schema::hasColumns('conversations', [
            'blocked_at', 'blocked_by_user_id', 'manually_unread', 'buyer_avatar_path',
        ]));
    }

    public function test_phone_and_tag_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('conversations', ['has_phone', 'detected_phone']));
        $this->assertTrue(Schema::hasTable('messaging_tags'));
        $this->assertTrue(Schema::hasColumns('messaging_tags', ['id', 'tenant_id', 'name', 'color', 'created_at', 'updated_at']));
    }

    public function test_relay_messaging_avatar_stores_conversation_avatar(): void
    {
        $disk = (string) config('messaging.media_disk', 'local');
        Storage::fake($disk);
        Http::fake([
            'cdn.fb/*' => Http::response('IMAGEBYTES', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $tenant = Tenant::create(['name' => 'AvShop']);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_A', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $conv = Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_a',
            'buyer_external_id' => 'psid_a', 'status' => 'open', 'last_message_at' => now(),
        ]);

        (new RelayMessagingAvatar(
            'conversation', $conv->id, 'https://cdn.fb/psidavatar.jpg'
        ))->handle(app(MessagingAvatarRelay::class));

        $conv->refresh();
        $this->assertNotNull($conv->buyer_avatar_path);
        Storage::disk($disk)->assertExists($conv->buyer_avatar_path);
    }

    private function fbAccount(): array
    {
        $tenant = Tenant::create(['name' => 'SyncShop']);
        // Set tenant context so ::query() assertions (TenantScope) find records.
        $this->app->make(CurrentTenant::class)->set($tenant);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_123', 'status' => 'active',
            'access_token' => 'PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $account->id, 'tenant_id' => $tenant->getKey(), 'messaging_enabled' => true,
        ]);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP', 'integrations.messaging_facebook_page.app_secret' => 'S',
            'messaging.backfill.days' => 90,
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        return [$tenant, $account];
    }

    public function test_backfill_ingests_conversations_and_messages_idempotently(): void
    {
        Bus::fake([
            RelayMessagingAvatar::class,
            DownloadInboundMedia::class,
        ]);
        [$tenant, $account] = $this->fbAccount();

        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response([
                'data' => [[
                    'id' => 't_aaa', 'updated_time' => now()->subDay()->toIso8601String(), 'message_count' => 2,
                    'participants' => ['data' => [
                        ['id' => 'PAGE_123', 'name' => 'Page'], ['id' => 'PSID_999', 'name' => 'A'],
                    ]],
                ]],
                'paging' => [],
            ], 200),
            'graph.facebook.com/*t_aaa*' => Http::response([
                'id' => 't_aaa',
                'messages' => ['data' => [
                    ['id' => 'm_1', 'message' => 'hi', 'created_time' => now()->subDay()->toIso8601String(), 'from' => ['id' => 'PSID_999']],
                    ['id' => 'm_2', 'message' => 'hello', 'created_time' => now()->subDay()->toIso8601String(), 'from' => ['id' => 'PAGE_123']],
                ]],
            ], 200),
            'graph.facebook.com/*' => Http::response(['name' => 'Page', 'picture' => ['data' => ['url' => 'https://cdn.fb/p.jpg']]], 200),
        ]);

        BackfillMessagingChannel::dispatchSync($account->id);

        $this->assertDatabaseHas('conversations', [
            'channel_account_id' => $account->id, 'external_conversation_id' => 'PSID_999', 'buyer_name' => 'A',
        ]);
        $this->assertSame(2, Message::query()->count());
        $meta = MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('done', $meta->sync_status);
        $this->assertSame(2, (int) $meta->sync_message_count);
        $this->assertSame(1, (int) $meta->sync_done_conversations);
        $this->assertNotNull($meta->last_synced_at);

        // Re-run ⇒ dedupe, no duplicates.
        BackfillMessagingChannel::dispatchSync($account->id);
        $this->assertSame(2, Message::query()->count());
    }

    public function test_backfill_stops_at_cutoff_and_skips_old_conversations(): void
    {
        Bus::fake([
            RelayMessagingAvatar::class,
            DownloadInboundMedia::class,
        ]);
        [$tenant, $account] = $this->fbAccount();

        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response([
                'data' => [[
                    'id' => 't_old', 'updated_time' => now()->subDays(120)->toIso8601String(), 'message_count' => 1,
                    'participants' => ['data' => [['id' => 'PAGE_123'], ['id' => 'PSID_OLD']]],
                ]],
                'paging' => ['cursors' => ['after' => 'C2'], 'next' => 'https://x'],
            ], 200),
            'graph.facebook.com/*' => Http::response(['name' => 'Page'], 200),
        ]);

        BackfillMessagingChannel::dispatchSync($account->id);

        $this->assertDatabaseMissing('conversations', ['external_conversation_id' => 'PSID_OLD']);
        $meta = MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('done', $meta->sync_status);
    }

    private function activateProFor(Tenant $tenant): void
    {
        $this->seed(BillingPlanSeeder::class);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
    }

    private function ownerFor(Tenant $tenant): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        return $user;
    }

    public function test_sync_endpoint_dispatches_backfill_for_owner(): void
    {
        Bus::fake([BackfillMessagingChannel::class]);
        [$tenant, $account] = $this->fbAccount();
        $this->activateProFor($tenant);

        $this->actingAs($this->ownerFor($tenant))->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson("/api/v1/messaging/channels/{$account->id}/sync")
            ->assertStatus(202)
            ->assertJsonPath('data.ok', true);

        Bus::assertDispatched(BackfillMessagingChannel::class,
            fn ($job) => $job->channelAccountId === $account->id);

        $meta = MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('queued', $meta->sync_status);
    }

    public function test_reconcile_command_dispatches_incremental_backfill_for_active_pages(): void
    {
        Bus::fake([BackfillMessagingChannel::class]);
        [$tenant, $account] = $this->fbAccount();
        MessagingAccountMeta::query()->where('channel_account_id', $account->id)
            ->update(['last_synced_at' => now()->subHours(2)]);

        $this->artisan('messaging:reconcile-sync')->assertExitCode(0);

        Bus::assertDispatched(BackfillMessagingChannel::class,
            fn ($job) => $job->channelAccountId === $account->id && $job->sinceIso !== null);
    }

    public function test_full_backfill_resets_stale_cursor(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        [$tenant, $account] = $this->fbAccount();
        MessagingAccountMeta::query()->where('channel_account_id', $account->id)->update(['sync_cursor' => 'STALE_CURSOR']);
        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response(['data' => [], 'paging' => []], 200),
            'graph.facebook.com/*' => Http::response(['name' => 'Page'], 200),
        ]);
        BackfillMessagingChannel::dispatchSync($account->id); // sinceIso null = full
        // The first conversations fetch must NOT carry the stale after-cursor.
        Http::assertSent(fn ($r) => str_contains($r->url(), '/conversations') && ! str_contains($r->url(), 'after=STALE_CURSOR'));
    }

    public function test_incremental_backfill_keeps_cursor(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        [$tenant, $account] = $this->fbAccount();
        MessagingAccountMeta::query()->where('channel_account_id', $account->id)->update(['sync_cursor' => 'KEEP_CURSOR']);
        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response(['data' => [], 'paging' => []], 200),
            'graph.facebook.com/*' => Http::response(['name' => 'Page'], 200),
        ]);
        (new BackfillMessagingChannel($account->id, now()->subHour()->toIso8601String()))->handle(app(MessagingRegistry::class), app(MessageIngestionService::class));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/conversations') && str_contains($r->url(), 'after=KEEP_CURSOR'));
    }

    public function test_channels_index_returns_avatar_count_and_sync(): void
    {
        [$tenant, $account] = $this->fbAccount();
        $this->activateProFor($tenant);
        MessagingAccountMeta::query()->where('channel_account_id', $account->id)
            ->update(['sync_status' => 'done', 'sync_message_count' => 5, 'sync_done_conversations' => 2]);
        Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id, 'provider' => 'facebook_page',
            'external_conversation_id' => 'p1', 'buyer_external_id' => 'p1', 'status' => 'open',
            'message_count' => 3, 'last_message_at' => now(),
        ]);

        $this->actingAs($this->ownerFor($tenant))->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->getJson('/api/v1/messaging/channels')->assertOk()
            ->assertJsonPath('data.0.message_count', 3)
            ->assertJsonPath('data.0.sync.status', 'done')
            ->assertJsonPath('data.0.sync.message_count', 5);
    }

    /**
     * Khi page_avatar_synced_at đã set VÀ conversation.buyer_avatar_path đã có
     * ⇒ backfill không dispatch RelayMessagingAvatar lần nào (tránh re-download mỗi giờ).
     * Ngược lại, fresh account (page_avatar_synced_at = null) vẫn dispatch page relay.
     */
    public function test_backfill_skips_avatar_relay_when_already_present(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        [$tenant, $account] = $this->fbAccount();

        // Mark page avatar đã relay.
        MessagingAccountMeta::query()->where('channel_account_id', $account->id)
            ->update(['page_avatar_synced_at' => now()->subHour()]);

        // Tạo conversation đã có buyer_avatar_path.
        Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'PSID_AV',
            'buyer_external_id' => 'PSID_AV', 'buyer_name' => 'Buyer AV',
            'status' => 'open', 'last_message_at' => now(),
            'buyer_avatar_path' => 'tenants/1/messaging/2025/01/1/avatar.jpg',
        ]);

        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response([
                'data' => [[
                    'id' => 't_av', 'updated_time' => now()->subHour()->toIso8601String(), 'message_count' => 0,
                    'participants' => ['data' => [
                        ['id' => 'PAGE_123', 'name' => 'Page'], ['id' => 'PSID_AV', 'name' => 'Buyer AV'],
                    ]],
                ]],
                'paging' => [],
            ], 200),
            // messages endpoint trả empty
            'graph.facebook.com/*t_av*' => Http::response([
                'id' => 't_av', 'messages' => ['data' => []],
            ], 200),
            // catch-all — không được gọi fetchPageProfile / fetchUserProfile vì guard đã chặn
            'graph.facebook.com/*' => Http::response([], 200),
        ]);

        BackfillMessagingChannel::dispatchSync($account->id);

        // Không được relay thêm lần nào — page đã sync, buyer_avatar_path đã có.
        Bus::assertNotDispatched(RelayMessagingAvatar::class);
    }

    /**
     * Fresh account (page_avatar_synced_at = null) phải vẫn dispatch relay cho page avatar.
     */
    public function test_backfill_dispatches_page_relay_on_first_run(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        [$tenant, $account] = $this->fbAccount();

        // page_avatar_synced_at = null (fresh — default từ fbAccount()).

        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response(['data' => [], 'paging' => []], 200),
            // fetchPageProfile trả về avatar URL — connector dùng endpoint này.
            'graph.facebook.com/*' => Http::response([
                'name' => 'Page',
                'picture' => ['data' => ['url' => 'https://cdn.fb/page.jpg']],
            ], 200),
        ]);

        BackfillMessagingChannel::dispatchSync($account->id);

        Bus::assertDispatched(RelayMessagingAvatar::class, fn ($job) => $job->target === 'page');
    }
}
