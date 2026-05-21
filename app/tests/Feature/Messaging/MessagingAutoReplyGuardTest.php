<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Jobs\SendMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Safety-guard tests for messaging auto-reply / AI / send paths.
 *
 * (a) First Lazada sync: MessageReceived NOT dispatched when fireInboundEvent=false,
 *     but DownloadInboundMedia IS dispatched for pending attachments.
 *     With fireInboundEvent=true (default), MessageReceived IS dispatched.
 *
 * (b) Blocked conversation send: SendMessage marks message failed with
 *     failure_code='conversation_blocked' and does NOT send outbound HTTP.
 */
class MessagingAutoReplyGuardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);

        $this->tenant = Tenant::create(['name' => 'GuardShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_guard_1',
            'shop_name' => 'Guard Shop',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // (a) fireEventsForNewMessage — first-sync guard
    // -------------------------------------------------------------------------

    /**
     * When fireInboundEvent=false (first Lazada sync), MessageReceived must NOT
     * be dispatched, so no auto-reply / AI listener is invoked for backlog messages.
     */
    public function test_fire_events_suppresses_message_received_on_first_sync(): void
    {
        Event::fake([MessageReceived::class]);
        Queue::fake([DownloadInboundMedia::class]);

        $ingest = app(MessageIngestionService::class);
        $dto = new MessageDTO(
            externalConversationId: 'conv_first_sync',
            externalMessageId: 'msg_first_sync_1',
            buyerExternalId: 'buyer_1',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'Tin nhắn cũ từ backlog',
        );

        $res = $ingest->ingest($this->account, $dto);

        // Simulate first sync: fireInboundEvent = false
        $ingest->fireEventsForNewMessage(
            $res['conversation'],
            $res['message'],
            $res['conversation']->wasRecentlyCreated,
            false,
        );

        Event::assertNotDispatched(MessageReceived::class);
    }

    /**
     * Sanity / control: with fireInboundEvent=true (default, incremental sync or
     * webhook path), MessageReceived IS dispatched.
     */
    public function test_fire_events_dispatches_message_received_on_incremental_sync(): void
    {
        Event::fake([MessageReceived::class]);

        $ingest = app(MessageIngestionService::class);
        $dto = new MessageDTO(
            externalConversationId: 'conv_incr_sync',
            externalMessageId: 'msg_incr_sync_1',
            buyerExternalId: 'buyer_2',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'Tin nhắn mới từ incremental sync',
        );

        $res = $ingest->ingest($this->account, $dto);

        // Default 4th arg = true (or explicit true)
        $ingest->fireEventsForNewMessage(
            $res['conversation'],
            $res['message'],
            $res['conversation']->wasRecentlyCreated,
            true,
        );

        Event::assertDispatched(MessageReceived::class, function (MessageReceived $e) use ($res): bool {
            return $e->messageId === $res['message']->id;
        });
    }

    /**
     * Even when fireInboundEvent=false, DownloadInboundMedia MUST still be dispatched
     * for pending attachments so media from backlog is not lost.
     */
    public function test_fire_events_still_dispatches_media_download_on_first_sync(): void
    {
        Event::fake([MessageReceived::class]);
        Queue::fake([DownloadInboundMedia::class]);

        $ingest = app(MessageIngestionService::class);
        $dto = new MessageDTO(
            externalConversationId: 'conv_media_first',
            externalMessageId: 'msg_media_first_1',
            buyerExternalId: 'buyer_3',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Image,
            body: null,
            attachments: [
                new MediaRefDTO(
                    kind: MessageKind::Image,
                    mime: 'image/jpeg',
                    sizeBytes: 12345,
                    externalUrl: 'https://cdn.lazada.vn/img/backlog.jpg',
                    storagePath: null, // pending — must be relayed
                ),
            ],
        );

        $res = $ingest->ingest($this->account, $dto);

        // First sync: suppress event but still relay media
        $ingest->fireEventsForNewMessage(
            $res['conversation'],
            $res['message'],
            $res['conversation']->wasRecentlyCreated,
            false,
        );

        // MessageReceived must NOT be dispatched
        Event::assertNotDispatched(MessageReceived::class);

        // DownloadInboundMedia MUST be dispatched for the pending attachment
        Queue::assertPushed(DownloadInboundMedia::class, 1);
    }

    // -------------------------------------------------------------------------
    // (b) SendMessage refuses blocked conversations
    // -------------------------------------------------------------------------

    /**
     * When a conversation has blocked_at set, SendMessage must mark the message
     * as failed with failure_code='conversation_blocked' and must NOT make any
     * outbound HTTP request to the channel API.
     */
    public function test_send_message_fails_when_conversation_is_blocked(): void
    {
        Http::fake(); // intercept all HTTP — assert nothing is sent

        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_blocked_send',
            'buyer_external_id' => 'buyer_blocked',
            'status' => Conversation::STATUS_OPEN,
            'blocked_at' => now(),
            'last_message_at' => now(),
        ]);

        $message = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $conv->id,
            'external_message_id' => null,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'auto-reply que se não deve enviar',
            'delivery_status' => Message::STATUS_PENDING,
            'sent_at' => null,
        ]);

        // Run the job synchronously (no queue)
        (new SendMessage($message->id))->handle(
            app(MessagingRegistry::class),
        );

        // Message must be marked failed with the blocked failure code
        $message->refresh();
        $this->assertSame(Message::STATUS_FAILED, $message->delivery_status);
        $this->assertSame('conversation_blocked', $message->failure_code);

        // No HTTP calls should have been made to any channel API
        Http::assertNothingSent();
    }

    /**
     * Sending to a non-blocked conversation must NOT be refused by the blocked guard
     * (regression: ensure normal send path is not accidentally broken).
     */
    public function test_send_message_does_not_refuse_unblocked_conversation(): void
    {
        // Arrange: unblocked conversation; use 'manual' provider which does not
        // make real HTTP calls (or fake it so nothing actually hits the network).
        Http::fake();

        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_unblocked_send',
            'buyer_external_id' => 'buyer_ok',
            'status' => Conversation::STATUS_OPEN,
            'blocked_at' => null,
            'last_message_at' => now(),
        ]);

        $message = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $conv->id,
            'external_message_id' => null,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Xin chào anh/chị!',
            'delivery_status' => Message::STATUS_PENDING,
            'sent_at' => null,
        ]);

        // The job may fail for other reasons (no real connector send), but must NOT
        // fail due to conversation_blocked. We only assert the failure code differs.
        try {
            (new SendMessage($message->id))->handle(
                app(MessagingRegistry::class),
            );
        } catch (\Throwable) {
            // Expected: manual connector send may throw in test env — that's fine.
        }

        $message->refresh();
        $this->assertNotSame('conversation_blocked', $message->failure_code,
            'Unblocked conversation must not be refused with conversation_blocked code.');
    }
}
