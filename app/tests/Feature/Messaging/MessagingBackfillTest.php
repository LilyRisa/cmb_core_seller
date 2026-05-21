<?php

namespace Tests\Feature\Messaging;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
        \Illuminate\Support\Facades\Storage::fake($disk);
        \Illuminate\Support\Facades\Http::fake([
            'cdn.fb/*' => \Illuminate\Support\Facades\Http::response('IMAGEBYTES', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::create(['name' => 'AvShop']);
        $account = \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_A', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $conv = \CMBcoreSeller\Modules\Messaging\Models\Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'psid_a',
            'buyer_external_id' => 'psid_a', 'status' => 'open', 'last_message_at' => now(),
        ]);

        (new \CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar(
            'conversation', $conv->id, 'https://cdn.fb/psidavatar.jpg'
        ))->handle(app(\CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay::class));

        $conv->refresh();
        $this->assertNotNull($conv->buyer_avatar_path);
        \Illuminate\Support\Facades\Storage::disk($disk)->assertExists($conv->buyer_avatar_path);
    }
}
