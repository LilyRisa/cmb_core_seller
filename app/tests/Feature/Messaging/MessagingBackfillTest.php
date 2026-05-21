<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
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

    private function activateProFor(\CMBcoreSeller\Modules\Tenancy\Models\Tenant $tenant): void
    {
        $this->seed(\CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder::class);
        $plan = \CMBcoreSeller\Modules\Billing\Models\Plan::query()->where('code', \CMBcoreSeller\Modules\Billing\Models\Plan::CODE_PRO)->firstOrFail();
        \CMBcoreSeller\Modules\Billing\Models\Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => \CMBcoreSeller\Modules\Billing\Models\Subscription::STATUS_ACTIVE,
            'billing_cycle' => \CMBcoreSeller\Modules\Billing\Models\Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
    }

    private function ownerFor(\CMBcoreSeller\Modules\Tenancy\Models\Tenant $tenant): \CMBcoreSeller\Models\User
    {
        $user = \CMBcoreSeller\Models\User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner->value]);

        return $user;
    }

    public function test_sync_endpoint_dispatches_backfill_for_owner(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class]);
        [$tenant, $account] = $this->fbAccount();
        $this->activateProFor($tenant);

        $this->actingAs($this->ownerFor($tenant))->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson("/api/v1/messaging/channels/{$account->id}/sync")
            ->assertStatus(202)
            ->assertJsonPath('data.ok', true);

        \Illuminate\Support\Facades\Bus::assertDispatched(\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class,
            fn ($job) => $job->channelAccountId === $account->id);

        $meta = \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('queued', $meta->sync_status);
    }

    public function test_reconcile_command_dispatches_incremental_backfill_for_active_pages(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class]);
        [$tenant, $account] = $this->fbAccount();
        \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->where('channel_account_id', $account->id)
            ->update(['last_synced_at' => now()->subHours(2)]);

        $this->artisan('messaging:reconcile-sync')->assertExitCode(0);

        \Illuminate\Support\Facades\Bus::assertDispatched(\CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel::class,
            fn ($job) => $job->channelAccountId === $account->id && $job->sinceIso !== null);
    }

    public function test_channels_index_returns_avatar_count_and_sync(): void
    {
        [$tenant, $account] = $this->fbAccount();
        $this->activateProFor($tenant);
        \CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta::query()->where('channel_account_id', $account->id)
            ->update(['sync_status' => 'done', 'sync_message_count' => 5, 'sync_done_conversations' => 2]);
        \CMBcoreSeller\Modules\Messaging\Models\Conversation::query()->create([
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
}
