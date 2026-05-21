<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
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
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Facebook real-time comment ingestion via `feed` webhook (SPEC-0024 Phase C).
 *
 * Covers:
 *  1. Connector parse: feed `add` comment change → DTO with correct comment fields +
 *     thread_type marker; page's own comment skipped; reply groups under parent.
 *  2. End-to-end webhook: POST a valid signed `feed` comment payload → conversation
 *     with thread_type='comment' + meta.fb_post_id + inbound message ingested.
 *  3. Idempotent on replay.
 *  4. No auto-reply (MessageReceived event NOT fired for comment events).
 */
class FacebookCommentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'fb-app-secret-comments';

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
    // 1. Connector parse tests
    // -------------------------------------------------------------------------

    public function test_parse_feed_comment_add_emits_correct_dto(): void
    {
        $connector = $this->makeConnector();
        $request = $this->feedCommentRequest([
            'field' => 'feed',
            'value' => [
                'item' => 'comment',
                'verb' => 'add',
                'comment_id' => 'CMT_001',
                'post_id' => 'POST_XYZ',
                'from' => ['id' => 'BUYER_99', 'name' => 'Nguyễn Văn B'],
                'message' => 'Còn hàng không shop?',
                'created_time' => 1716200000,
            ],
        ], pageId: 'PAGE_001');

        $events = $connector->parseWebhookEvents($request);

        $this->assertCount(1, $events);
        $dto = $events[0];

        $this->assertSame('message_received', $dto->type);
        $this->assertSame('facebook_page', $dto->provider);
        $this->assertSame('PAGE_001', $dto->externalShopId);
        // Top-level comment: externalConversationId = comment_id (no parent)
        $this->assertSame('CMT_001', $dto->externalConversationId);
        $this->assertSame('CMT_001', $dto->externalMessageId);
        $this->assertSame('BUYER_99', $dto->buyerExternalId);
        $this->assertSame('Còn hàng không shop?', $dto->body);
        $this->assertNotNull($dto->occurredAt);
        // Thread markers
        $this->assertSame('comment', $dto->threadType);
        $this->assertSame('POST_XYZ', $dto->threadMeta['fb_post_id'] ?? null);
        $this->assertSame('CMT_001', $dto->threadMeta['fb_comment_id'] ?? null);
    }

    public function test_parse_feed_comment_edited_emits_dto(): void
    {
        $connector = $this->makeConnector();
        $request = $this->feedCommentRequest([
            'field' => 'feed',
            'value' => [
                'item' => 'comment',
                'verb' => 'edited',
                'comment_id' => 'CMT_002',
                'post_id' => 'POST_XYZ',
                'from' => ['id' => 'BUYER_77'],
                'message' => 'Đã sửa comment',
            ],
        ], pageId: 'PAGE_001');

        $events = $connector->parseWebhookEvents($request);
        $this->assertCount(1, $events);
        $this->assertSame('message_received', $events[0]->type);
    }

    public function test_parse_feed_comment_page_own_comment_skipped(): void
    {
        // from.id === page id → page's own reply, should NOT be ingested as inbound.
        $connector = $this->makeConnector();
        $request = $this->feedCommentRequest([
            'field' => 'feed',
            'value' => [
                'item' => 'comment',
                'verb' => 'add',
                'comment_id' => 'CMT_PAGE_REPLY',
                'post_id' => 'POST_XYZ',
                'from' => ['id' => 'PAGE_001', 'name' => 'Shop'],
                'message' => 'Cảm ơn bạn đã quan tâm!',
            ],
        ], pageId: 'PAGE_001');

        $events = $connector->parseWebhookEvents($request);
        // Page's own comment → no events emitted
        $this->assertCount(0, $events);
    }

    public function test_parse_feed_reply_groups_under_parent_comment(): void
    {
        // When parent_id is present, it's a reply → externalConversationId = parent_id.
        $connector = $this->makeConnector();
        $request = $this->feedCommentRequest([
            'field' => 'feed',
            'value' => [
                'item' => 'comment',
                'verb' => 'add',
                'comment_id' => 'REPLY_001',
                'parent_id' => 'CMT_PARENT',
                'post_id' => 'POST_XYZ',
                'from' => ['id' => 'BUYER_55'],
                'message' => 'Cảm ơn shop!',
            ],
        ], pageId: 'PAGE_001');

        $events = $connector->parseWebhookEvents($request);

        $this->assertCount(1, $events);
        $dto = $events[0];
        // Conversation = parent comment thread
        $this->assertSame('CMT_PARENT', $dto->externalConversationId);
        // Message id = the reply itself
        $this->assertSame('REPLY_001', $dto->externalMessageId);
        // Thread meta fb_comment_id = parent (thread root)
        $this->assertSame('CMT_PARENT', $dto->threadMeta['fb_comment_id'] ?? null);
    }

    public function test_parse_feed_non_comment_item_not_emitted(): void
    {
        // item=photo, item=status etc. → ignored
        $connector = $this->makeConnector();
        $request = $this->feedCommentRequest([
            'field' => 'feed',
            'value' => [
                'item' => 'photo',
                'verb' => 'add',
                'post_id' => 'POST_XYZ',
            ],
        ], pageId: 'PAGE_001');

        $events = $connector->parseWebhookEvents($request);
        $this->assertCount(0, $events);
    }

    public function test_parse_feed_comment_remove_verb_not_emitted(): void
    {
        $connector = $this->makeConnector();
        $request = $this->feedCommentRequest([
            'field' => 'feed',
            'value' => [
                'item' => 'comment',
                'verb' => 'remove',
                'comment_id' => 'CMT_DEL',
                'post_id' => 'POST_XYZ',
                'from' => ['id' => 'BUYER_11'],
            ],
        ], pageId: 'PAGE_001');

        $events = $connector->parseWebhookEvents($request);
        $this->assertCount(0, $events);
    }

    public function test_parse_mixed_batch_messaging_and_feed_change(): void
    {
        // A POST containing both a messaging event and a feed change → both processed.
        $connector = $this->makeConnector();
        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'PAGE_001',
                'messaging' => [[
                    'sender' => ['id' => 'PSID_X'],
                    'message' => ['mid' => 'mid_dm', 'text' => 'DM message'],
                ]],
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'item' => 'comment',
                        'verb' => 'add',
                        'comment_id' => 'CMT_MIXED',
                        'post_id' => 'POST_XYZ',
                        'from' => ['id' => 'BUYER_MIX'],
                        'message' => 'comment in mixed batch',
                    ],
                ]],
            ]],
        ]);

        $req = Request::create('/webhook/messaging/facebook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $events = $connector->parseWebhookEvents($req);

        $this->assertCount(2, $events);
        $types = array_column($events, 'type');
        $this->assertCount(2, array_filter($types, fn ($t) => $t === 'message_received'));
    }

    // -------------------------------------------------------------------------
    // 2. End-to-end webhook tests
    // -------------------------------------------------------------------------

    public function test_post_feed_comment_webhook_creates_comment_conversation_and_message(): void
    {
        [$tenant, $account] = $this->fbAccount();

        $payload = $this->feedCommentPayload('PAGE_FB', 'CMT_E2E', 'POST_SALE', 'BUYER_42', 'Còn không shop?');
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        // POST to webhook → stores + dispatches job synchronously
        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk()
            ->assertJsonPath('stored', 1);

        // Process the job synchronously
        $event = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('external_id', 'CMT_E2E')
            ->first();
        $this->assertNotNull($event);

        (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        // Conversation with thread_type='comment'
        $conv = Conversation::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $account->id)
            ->where('external_conversation_id', 'CMT_E2E')
            ->first();

        $this->assertNotNull($conv);
        $this->assertSame('comment', $conv->thread_type);
        $this->assertSame('POST_SALE', $conv->meta['fb_post_id'] ?? null);
        $this->assertSame('CMT_E2E', $conv->meta['fb_comment_id'] ?? null);
        $this->assertSame('BUYER_42', $conv->buyer_external_id);

        // Inbound message
        $msg = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('external_message_id', 'CMT_E2E')
            ->first();

        $this->assertNotNull($msg);
        $this->assertSame('inbound', $msg->direction);
        $this->assertSame('text', $msg->kind);
        $this->assertSame('Còn không shop?', $msg->body);
    }

    public function test_feed_comment_webhook_is_idempotent_on_replay(): void
    {
        [$tenant, $account] = $this->fbAccount();

        $payload = $this->feedCommentPayload('PAGE_FB', 'CMT_IDEM', 'POST_IDEM', 'BUYER_IDM', 'Idempotent check');
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        // First POST
        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk();

        // Second POST (replay) → duplicate
        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk()
            ->assertJsonPath('note', 'duplicate');

        // Process job twice (idempotency at job level)
        $event = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('external_id', 'CMT_IDEM')
            ->first();

        $this->assertNotNull($event);

        $processJob = fn () => (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        $processJob();
        // Reset status to allow re-run
        $event->refresh();
        $event->forceFill(['status' => WebhookEvent::STATUS_PENDING])->save();
        $processJob();

        // Only 1 conversation + 1 message (deduped)
        $this->assertSame(1, Conversation::withoutGlobalScope(TenantScope::class)
            ->where('external_conversation_id', 'CMT_IDEM')->count());
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)
            ->where('external_message_id', 'CMT_IDEM')->count());
    }

    public function test_feed_comment_no_auto_reply_event_fired(): void
    {
        [$tenant, $account] = $this->fbAccount();

        // Intercept events
        Event::fake([MessageReceived::class]);

        $payload = $this->feedCommentPayload('PAGE_FB', 'CMT_NOREPLY', 'POST_NR', 'BUYER_NR', 'No auto reply please');
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk();

        $event = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('external_id', 'CMT_NOREPLY')
            ->first();

        (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        // MessageReceived MUST NOT be dispatched for comment events
        Event::assertNotDispatched(MessageReceived::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a signed Symfony Request with a single feed change event. */
    private function feedCommentRequest(array $change, string $pageId): Request
    {
        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => $pageId,
                'changes' => [$change],
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

    /** JSON-encoded Facebook webhook payload with a single feed comment change. */
    private function feedCommentPayload(
        string $pageId,
        string $commentId,
        string $postId,
        string $fromId,
        string $message,
    ): string {
        return json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => $pageId,
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'item' => 'comment',
                        'verb' => 'add',
                        'comment_id' => $commentId,
                        'post_id' => $postId,
                        'from' => ['id' => $fromId, 'name' => 'Test Buyer'],
                        'message' => $message,
                        'created_time' => time(),
                    ],
                ]],
            ]],
        ]);
    }

    /** Create a connected facebook_page account with matching external_shop_id=PAGE_FB. */
    private function fbAccount(): array
    {
        $tenant = Tenant::create(['name' => 'CommentWebhookShop']);
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
