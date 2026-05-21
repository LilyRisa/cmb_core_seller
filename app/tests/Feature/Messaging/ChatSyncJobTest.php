<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\SyncConversationsForShop;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * TDD — SyncConversationsForShop job (Phase C1).
 *
 * Covers:
 *  - Lazada: pages conversations + messages, ingests into DB, updates
 *    MessagingAccountMeta (sync_status='done', last_synced_at set).
 *  - Shopee (and any webhook-only connector): job is a no-op — no exception,
 *    no conversations created, status row left untouched.
 */
class ChatSyncJobTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'ChatSyncShop']);
    }

    // -----------------------------------------------------------------------
    // Helper: Lazada /im/session/list response with 1 session
    // -----------------------------------------------------------------------
    private static function lazadaSessionListPage(bool $hasMore = false, string $lastSessionId = 'SESSION_1', string $nextStartTime = '0'): array
    {
        return [
            'code' => '0',
            'data' => [
                'session_list' => [
                    [
                        'session_id' => 'SESSION_1',
                        'buyer_id' => 'BUYER_1',
                        'title' => 'Nguyễn Văn A',
                        'summary' => 'Chào shop',
                        'last_message_time' => (string) (int) (microtime(true) * 1000),
                        'unread_count' => 1,
                    ],
                ],
                'has_more' => $hasMore,
                'last_session_id' => $lastSessionId,
                'next_start_time' => $nextStartTime,
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Helper: Lazada /im/message/list response with 1 text message
    // -----------------------------------------------------------------------
    private static function lazadaMessageListPage(bool $hasMore = false): array
    {
        return [
            'code' => '0',
            'data' => [
                'message_list' => [
                    [
                        'message_id' => 'MSG_1',
                        'session_id' => 'SESSION_1',
                        'from_account_id' => 'BUYER_1',
                        'from_account_type' => 1, // buyer = inbound
                        'template_id' => 1,       // text
                        'content' => json_encode(['txt' => 'Chào shop, cho mình hỏi giá?']),
                        'send_time' => (string) (int) (microtime(true) * 1000),
                    ],
                ],
                'has_more' => $hasMore,
                'last_message_id' => '',
            ],
        ];
    }

    /**
     * Happy path: Lazada account with 1 conversation + 1 message → created in DB,
     * MessagingAccountMeta saved with sync_status='done' and last_synced_at set.
     */
    public function test_syncs_lazada_conversations_and_messages(): void
    {
        // Arrange — config Lazada
        config([
            'integrations.messaging' => ['lazada_chat'],
            'integrations.lazada' => [
                'app_key' => 'K',
                'app_secret' => 'S',
                'base_url' => 'https://api.lazada.vn/rest',
            ],
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $acct = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada',
            'external_shop_id' => 'S1',
            'shop_name' => 'Lazada VN Test',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'T',
            'messaging_enabled' => true,
        ]);

        // Fake HTTP — page 1 of sessions (no more pages), page 1 of messages (no more pages)
        Http::fake([
            'https://api.lazada.vn/rest/im/session/list*' => Http::response(
                self::lazadaSessionListPage(hasMore: false),
                200,
            ),
            'https://api.lazada.vn/rest/im/message/list*' => Http::response(
                self::lazadaMessageListPage(hasMore: false),
                200,
            ),
        ]);

        // Act
        (new SyncConversationsForShop($acct->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
        );

        // Assert — conversation created with provider='lazada_chat'
        $this->assertDatabaseHas('conversations', [
            'channel_account_id' => $acct->id,
            'provider' => 'lazada_chat',
            'external_conversation_id' => 'SESSION_1',
        ]);

        // Assert — message created
        $this->assertDatabaseHas('messages', [
            'external_message_id' => 'MSG_1',
        ]);

        // Assert — meta row updated
        $meta = MessagingAccountMeta::withoutGlobalScopes()->where('channel_account_id', $acct->id)->firstOrFail();
        $this->assertSame('done', $meta->sync_status);
        $this->assertNotNull($meta->last_synced_at);
    }

    /**
     * Shopee (inbound.polling=false) → job returns early without any exception,
     * no conversations are created, and no 'running' status is left dangling.
     */
    public function test_noop_when_connector_lacks_polling(): void
    {
        // Arrange — config Shopee
        ShopeeFixtures::configure();
        config(['integrations.messaging' => ['shopee_chat']]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $acct = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => 'SHOPEE_SHOP_1',
            'shop_name' => 'Shopee VN Test',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'T',
            'messaging_enabled' => true,
        ]);

        // No Http::fake needed — connector must return before making any HTTP call.
        Http::fake([]);

        // Act — must not throw
        (new SyncConversationsForShop($acct->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
        );

        // Assert — no conversations created
        $this->assertDatabaseMissing('conversations', ['channel_account_id' => $acct->id]);

        // Assert — no dangling 'running' status (meta row either absent or not 'running')
        $meta = MessagingAccountMeta::withoutGlobalScopes()->where('channel_account_id', $acct->id)->first();
        $this->assertTrue($meta === null || $meta->sync_status !== 'running');
    }
}
