<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Events\PostbackReceived;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Postback (bấm nút) inbound pipeline (Flow Builder S2 §6): webhook postback →
 * KHÔNG ingest tin nhắn → phát PostbackReceived cho hội thoại DM tương ứng.
 */
class PostbackWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'fb-postback-secret';

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

    public function test_postback_dispatches_event_and_does_not_ingest_message(): void
    {
        Event::fake([PostbackReceived::class]);
        [$tenant, $account, $conversation] = $this->fixture();

        $payload = $this->postbackPayload('PAGE_FB', 'PSID_1', 'm_pb_1', '{"t":"flow","n":"ask","h":"b_buy"}');
        $this->postWebhook($payload)->assertOk()->assertJsonPath('stored', 1);

        $this->runLatestPostbackJob();

        Event::assertDispatched(PostbackReceived::class, function (PostbackReceived $e) use ($conversation) {
            return $e->conversationId === (int) $conversation->id
                && $e->payload === '{"t":"flow","n":"ask","h":"b_buy"}'
                && $e->externalMessageId === 'm_pb_1';
        });

        // Postback KHÔNG tạo tin nhắn trong hội thoại.
        $this->assertSame(0, Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conversation->id)->count());
    }

    public function test_postback_for_unknown_conversation_is_ignored_without_error(): void
    {
        Event::fake([PostbackReceived::class]);
        $this->fbAccount(); // account exists, but no conversation for PSID_UNKNOWN

        $payload = $this->postbackPayload('PAGE_FB', 'PSID_UNKNOWN', 'm_pb_2', '{"t":"flow","n":"ask","h":"b_buy"}');
        $this->postWebhook($payload)->assertOk()->assertJsonPath('stored', 1);

        $event = $this->runLatestPostbackJob();

        Event::assertNotDispatched(PostbackReceived::class);
        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $event->fresh()->status);
    }

    // --- helpers ---------------------------------------------------------

    /** @return array{0:Tenant,1:ChannelAccount,2:Conversation} */
    private function fixture(): array
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

        return [$tenant, $account, $conversation];
    }

    /** @return array{0:Tenant,1:ChannelAccount} */
    private function fbAccount(): array
    {
        $tenant = Tenant::create(['name' => 'PostbackShop'.uniqid()]);
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

    private function postbackPayload(string $pageId, string $psid, string $mid, string $payload): string
    {
        return json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => $pageId,
                'messaging' => [[
                    'sender' => ['id' => $psid],
                    'recipient' => ['id' => $pageId],
                    'timestamp' => time() * 1000,
                    'postback' => ['mid' => $mid, 'title' => 'Mua hàng', 'payload' => $payload],
                ]],
            ]],
        ]);
    }

    private function postWebhook(string $payload): TestResponse
    {
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        return $this->call('POST', '/webhook/messaging/facebook_page', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload);
    }

    /** Run the stored postback webhook job synchronously (idempotent no-op if sync queue already ran it). */
    private function runLatestPostbackJob(): WebhookEvent
    {
        $event = WebhookEvent::query()
            ->where('provider', 'messaging.facebook_page')
            ->where('event_type', MessagingWebhookEventDTO::TYPE_POSTBACK)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);

        (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        return $event;
    }
}
