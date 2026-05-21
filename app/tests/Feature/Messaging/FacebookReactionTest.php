<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Facebook Messenger reaction (❤️ 😆 👍 …) ingestion pipeline.
 *
 * Covers:
 *  1. Connector parse: a `reaction` messaging event → DTO with type=message_reaction,
 *     externalMessageId = mid (target), emoji carried in raw['reaction'].
 *  2. registerWebhooks includes `message_reactions` in subscribed_fields.
 *  3. End-to-end: ingest a message, react → meta['reaction'] set; unreact → cleared.
 *  4. Dedupe: react + unreact are distinct webhook events (different dedupe keys).
 */
class FacebookReactionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'fb-reaction-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => self::SECRET,
            'integrations.messaging_facebook_page.verify_token' => 'VTOKEN',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    // -------------------------------------------------------------------------
    // 1. registerWebhooks includes message_reactions
    // -------------------------------------------------------------------------

    public function test_register_webhooks_includes_message_reactions(): void
    {
        $connector = $this->makeConnector();

        // Capture the subscribed_fields via Http::fake
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1,
            provider: 'facebook_page',
            externalShopId: 'PAGE_1',
            accessToken: 'tok',
        );
        $connector->registerWebhooks($auth);

        Http::assertSent(function (\Illuminate\Http\Client\Request $req) {
            $fields = (string) ($req->data()['subscribed_fields'] ?? '');

            return str_contains($fields, 'message_reactions');
        });
    }

    // -------------------------------------------------------------------------
    // 2. Connector parse: reaction event → correct DTO
    // -------------------------------------------------------------------------

    public function test_parse_reaction_event_produces_correct_dto(): void
    {
        $connector = $this->makeConnector();

        $request = $this->reactionRequest(
            pageId: 'PAGE_001',
            psid: 'PSID_BUYER',
            mid: 'm_target_123',
            action: 'react',
            reaction: 'love',
            emoji: '❤️',
        );

        $events = $connector->parseWebhookEvents($request);

        $this->assertCount(1, $events);
        $dto = $events[0];

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION, $dto->type);
        $this->assertSame('facebook_page', $dto->provider);
        $this->assertSame('PAGE_001', $dto->externalShopId);
        // externalConversationId = PSID of the reacting user (conversation key)
        $this->assertSame('PSID_BUYER', $dto->externalConversationId);
        // externalMessageId = the TARGET message mid
        $this->assertSame('m_target_123', $dto->externalMessageId);
        $this->assertSame('PSID_BUYER', $dto->buyerExternalId);
        // Emoji and action are in raw['reaction']
        $this->assertSame('❤️', $dto->raw['reaction']['emoji'] ?? null);
        $this->assertSame('react', $dto->raw['reaction']['action'] ?? null);
    }

    public function test_parse_unreact_event_produces_correct_dto(): void
    {
        $connector = $this->makeConnector();

        $request = $this->reactionRequest(
            pageId: 'PAGE_001',
            psid: 'PSID_BUYER',
            mid: 'm_target_123',
            action: 'unreact',
            reaction: 'love',
            emoji: '❤️',
        );

        $events = $connector->parseWebhookEvents($request);

        $this->assertCount(1, $events);
        $dto = $events[0];

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION, $dto->type);
        $this->assertSame('unreact', $dto->raw['reaction']['action'] ?? null);
    }

    // -------------------------------------------------------------------------
    // 3. End-to-end: react sets meta['reaction'], unreact clears it
    // -------------------------------------------------------------------------

    public function test_react_sets_meta_reaction_on_target_message(): void
    {
        [$tenant, $account, $conversation, $message] = $this->setupMessageFixture('m_1');

        // POST a react reaction webhook
        $payload = $this->reactionPayload(
            pageId: 'PAGE_FB',
            psid: 'PSID_1',
            mid: 'm_1',
            action: 'react',
            reaction: 'love',
            emoji: '❤️',
        );
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk()
            ->assertJsonPath('stored', 1);

        // Process the job synchronously
        $webhookEvent = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('event_type', MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION)
            ->latest('id')
            ->first();

        $this->assertNotNull($webhookEvent);

        (new ProcessMessagingWebhook($webhookEvent->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        $message->refresh();
        $this->assertSame('❤️', $message->meta['reaction'] ?? null);
        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $webhookEvent->fresh()->status);
    }

    public function test_unreact_clears_meta_reaction_on_target_message(): void
    {
        [$tenant, $account, $conversation, $message] = $this->setupMessageFixture('m_2');

        // Pre-set a reaction so we can clear it
        $message->meta = ['reaction' => '❤️'];
        $message->save();

        // POST an unreact reaction webhook
        $payload = $this->reactionPayload(
            pageId: 'PAGE_FB',
            psid: 'PSID_1',
            mid: 'm_2',
            action: 'unreact',
            reaction: 'love',
            emoji: '❤️',
        );
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk()
            ->assertJsonPath('stored', 1);

        $webhookEvent = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('event_type', MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION)
            ->latest('id')
            ->first();

        $this->assertNotNull($webhookEvent);

        (new ProcessMessagingWebhook($webhookEvent->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        $message->refresh();
        $this->assertNull($message->meta['reaction'] ?? null);
        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $webhookEvent->fresh()->status);
    }

    public function test_react_then_unreact_both_processed_due_to_dedupe_separation(): void
    {
        [$tenant, $account, $conversation, $message] = $this->setupMessageFixture('m_3');

        // POST react
        $reactPayload = $this->reactionPayload('PAGE_FB', 'PSID_1', 'm_3', 'react', 'love', '❤️');
        $reactSig = 'sha256='.hash_hmac('sha256', $reactPayload, self::SECRET);
        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $reactSig, 'CONTENT_TYPE' => 'application/json'], $reactPayload)
            ->assertOk()
            ->assertJsonPath('stored', 1);

        // POST unreact (must NOT be deduped against the react)
        $unreactPayload = $this->reactionPayload('PAGE_FB', 'PSID_1', 'm_3', 'unreact', 'love', '❤️');
        $unreactSig = 'sha256='.hash_hmac('sha256', $unreactPayload, self::SECRET);
        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $unreactSig, 'CONTENT_TYPE' => 'application/json'], $unreactPayload)
            ->assertOk()
            ->assertJsonPath('stored', 1); // stored=1, not duplicate

        // Two distinct webhook events in DB
        $reactionEvents = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('event_type', MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION)
            ->get();

        $this->assertCount(2, $reactionEvents);
    }

    public function test_reaction_on_missing_message_does_not_error(): void
    {
        [$tenant, $account] = $this->fbAccount();

        // POST a reaction for a message that was never ingested
        $payload = $this->reactionPayload(
            pageId: 'PAGE_FB',
            psid: 'PSID_1',
            mid: 'mid_nonexistent_999',
            action: 'react',
            reaction: 'love',
            emoji: '❤️',
        );
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk()
            ->assertJsonPath('stored', 1);

        $webhookEvent = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('event_type', MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION)
            ->latest('id')
            ->first();

        // Must not throw — gracefully marks as processed
        (new ProcessMessagingWebhook($webhookEvent->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $webhookEvent->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a signed Symfony Request for a single messaging-reaction event. */
    private function reactionRequest(
        string $pageId,
        string $psid,
        string $mid,
        string $action,
        string $reaction,
        string $emoji,
    ): Request {
        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => $pageId,
                'messaging' => [[
                    'sender' => ['id' => $psid],
                    'recipient' => ['id' => $pageId],
                    'timestamp' => time() * 1000,
                    'reaction' => [
                        'mid' => $mid,
                        'action' => $action,
                        'reaction' => $reaction,
                        'emoji' => $emoji,
                    ],
                ]],
            ]],
        ]);

        return Request::create(
            '/webhook/messaging/facebook',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload,
        );
    }

    /** JSON-encoded + signed Facebook webhook payload for a reaction event. */
    private function reactionPayload(
        string $pageId,
        string $psid,
        string $mid,
        string $action,
        string $reaction,
        string $emoji,
    ): string {
        return json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => $pageId,
                'messaging' => [[
                    'sender' => ['id' => $psid],
                    'recipient' => ['id' => $pageId],
                    'timestamp' => time() * 1000,
                    'reaction' => [
                        'mid' => $mid,
                        'action' => $action,
                        'reaction' => $reaction,
                        'emoji' => $emoji,
                    ],
                ]],
            ]],
        ]);
    }

    /**
     * Set up a tenant, account, conversation, and a single inbound message
     * with the given external_message_id.  Returns [$tenant, $account, $conversation, $message].
     */
    private function setupMessageFixture(string $mid): array
    {
        [$tenant, $account] = $this->fbAccount();

        $conversation = Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(),
            'channel_account_id' => $account->getKey(),
            'provider' => 'facebook_page',
            'external_conversation_id' => 'PSID_1',
            'buyer_external_id' => 'PSID_1',
            'status' => 'open',
            'thread_type' => 'message',
        ]);

        $message = Message::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(),
            'conversation_id' => $conversation->getKey(),
            'external_message_id' => $mid,
            'direction' => 'inbound',
            'kind' => 'text',
            'body' => 'Hello',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        return [$tenant, $account, $conversation, $message];
    }

    /** Create a connected facebook_page account with matching external_shop_id=PAGE_FB. */
    private function fbAccount(): array
    {
        $tenant = Tenant::create(['name' => 'ReactionTestShop'.uniqid()]);
        $this->app->make(CurrentTenant::class)->set($tenant);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_FB',
            'status' => 'active',
            'access_token' => 'PAGE_TOKEN',
            'messaging_enabled' => true,
        ]);

        return [$tenant, $account];
    }

    private function makeConnector(): FacebookPageConnector
    {
        /** @var FacebookPageConnector $connector */
        $connector = $this->app->make(MessagingRegistry::class)->for('facebook_page');

        return $connector;
    }
}
