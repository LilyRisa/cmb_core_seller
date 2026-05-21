<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * Phase B: verify that normalized _kind/_body/_attachments stored in webhook_events.payload
 * are correctly rebuilt by ProcessMessagingWebhook → Message + MessageAttachment created
 * with proper kind/external_url; DownloadInboundMedia is dispatched for pending attachments.
 *
 * SPEC-0024 Phase B — InboundMediaIngest.
 */
class InboundMediaIngestTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        ShopeeFixtures::configure();
        config([
            'integrations.messaging' => ['shopee_chat'],
            'integrations.channels' => ['manual', 'shopee'],
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $this->tenant = Tenant::create(['name' => 'InboundMediaTenant']);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => '77',
            'shop_name' => 'Media Test Shop',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'access_token' => 'ACCESS_MEDIA',
        ]);
    }

    private function createWebhookEvent(array $payloadExtra = []): WebhookEvent
    {
        return WebhookEvent::query()->create([
            'provider' => 'messaging.shopee_chat',
            'event_type' => MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            'external_id' => $payloadExtra['external_message_id'] ?? 'MSG_MEDIA_1',
            'external_shop_id' => '77',
            'status' => WebhookEvent::STATUS_PENDING,
            'signature_ok' => true,
            'headers' => [],
            'received_at' => now(),
            'attempts' => 0,
            'payload' => array_merge([
                'external_conversation_id' => 'CONV_MEDIA_1',
                'external_message_id' => 'MSG_MEDIA_1',
                'buyer_external_id' => 'BUYER_MEDIA_1',
            ], $payloadExtra),
        ]);
    }

    /** Run ProcessMessagingWebhook synchronously. */
    private function runJob(WebhookEvent $event): void
    {
        (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );
    }

    /**
     * Image message: _kind=image, _attachments with external_url → Message(kind=image) +
     * MessageAttachment(external_url set, status=pending) + DownloadInboundMedia queued.
     */
    public function test_image_message_creates_message_attachment_and_queues_download(): void
    {
        Queue::fake();

        $event = $this->createWebhookEvent([
            'external_message_id' => 'MSG_IMG_1',
            'external_conversation_id' => 'CONV_IMG_1',
            '_kind' => 'image',
            '_body' => null,
            '_attachments' => [
                [
                    'kind' => 'image',
                    'mime' => 'image/jpeg',
                    'size_bytes' => null,
                    'external_url' => 'https://cf.shopee.vn/file/abc_dynamic',
                    'storage_path' => null,
                    'filename' => null,
                    'width' => 400,
                    'height' => 711,
                    'duration_ms' => null,
                ],
            ],
        ]);

        $this->runJob($event);

        // Message must be created with kind=image.
        $msg = Message::withoutGlobalScopes()
            ->where('external_message_id', 'MSG_IMG_1')
            ->first();
        $this->assertNotNull($msg, 'Message phải được tạo');
        $this->assertSame('image', $msg->kind, 'Message.kind phải là image');
        $this->assertSame(1, $msg->attachments_count, 'attachments_count phải là 1');

        // MessageAttachment must exist with correct external_url and status=pending.
        $attachment = MessageAttachment::withoutGlobalScope(TenantScope::class)
            ->where('message_id', $msg->id)
            ->first();
        $this->assertNotNull($attachment, 'MessageAttachment phải được tạo');
        $this->assertSame('image', $attachment->kind);
        $this->assertSame('https://cf.shopee.vn/file/abc_dynamic', $attachment->external_url);
        $this->assertSame(MessageAttachment::STATUS_PENDING, $attachment->status, 'Attachment chưa download → status pending');
        $this->assertNull($attachment->storage_path, 'storage_path phải null (chưa download)');

        // DownloadInboundMedia must be dispatched for the pending attachment.
        Queue::assertPushed(DownloadInboundMedia::class, 1);

        // WebhookEvent marked processed.
        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $event->fresh()->status);
    }

    /**
     * Text message: _kind=text, _body set → Message(kind=text, body set), no attachments,
     * DownloadInboundMedia NOT queued.
     */
    public function test_text_message_stores_body_and_does_not_queue_download(): void
    {
        Queue::fake();

        $event = $this->createWebhookEvent([
            'external_message_id' => 'MSG_TEXT_1',
            'external_conversation_id' => 'CONV_TEXT_1',
            '_kind' => 'text',
            '_body' => 'Còn hàng không shop?',
            '_attachments' => [],
        ]);

        $this->runJob($event);

        $msg = Message::withoutGlobalScopes()
            ->where('external_message_id', 'MSG_TEXT_1')
            ->first();
        $this->assertNotNull($msg, 'Message phải được tạo');
        $this->assertSame('text', $msg->kind);
        $this->assertSame('Còn hàng không shop?', $msg->body);
        $this->assertSame(0, $msg->attachments_count);

        Queue::assertNotPushed(DownloadInboundMedia::class);
    }

    /**
     * Regression: existing manual connector path (no _kind/_body/_attachments keys) still
     * works — falls back to legacy payload['body']/payload['kind'] path.
     */
    public function test_legacy_manual_payload_without_normalized_keys_still_works(): void
    {
        Queue::fake();

        // Use manual connector account.
        $manualAccount = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_legacy_1',
            'shop_name' => 'Legacy Manual Shop',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);

        config(['integrations.messaging' => ['shopee_chat', 'manual']]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $event = WebhookEvent::query()->create([
            'provider' => 'messaging.manual',
            'event_type' => MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            'external_id' => 'MSG_LEGACY_1',
            'external_shop_id' => 'shop_legacy_1',
            'status' => WebhookEvent::STATUS_PENDING,
            'signature_ok' => true,
            'headers' => [],
            'received_at' => now(),
            'attempts' => 0,
            'payload' => [
                // Old-style payload: no _kind/_body/_attachments
                'external_conversation_id' => 'CONV_LEGACY_1',
                'external_message_id' => 'MSG_LEGACY_1',
                'buyer_external_id' => 'BUYER_LEGACY_1',
                'body' => 'Legacy message body',
                'kind' => 'text',
            ],
        ]);

        $this->runJob($event);

        $msg = Message::withoutGlobalScopes()
            ->where('external_message_id', 'MSG_LEGACY_1')
            ->first();
        $this->assertNotNull($msg, 'Legacy message phải được tạo');
        $this->assertSame('text', $msg->kind);
        $this->assertSame('Legacy message body', $msg->body);
    }
}
