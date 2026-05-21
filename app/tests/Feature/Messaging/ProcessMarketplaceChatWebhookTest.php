<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * Kiểm tra E2E: webhook shopee_chat → ProcessMessagingWebhook → conversation.provider
 * phải là 'shopee_chat', không phải 'shopee'.
 *
 * Bug: MessageIngestionService::ensureConversation() dùng $channelAccount->provider ('shopee')
 * thay vì $channelAccount->messagingConnectorCode() ('shopee_chat'). SendMessage / MessageController
 * tra registry theo conversation.provider → không tìm thấy 'shopee' → reply thất bại.
 *
 * Test này FAIL trước khi fix (conversation.provider = 'shopee'),
 * PASS sau khi fix (conversation.provider = 'shopee_chat').
 */
class ProcessMarketplaceChatWebhookTest extends TestCase
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
        // Registry đọc config khi bind — forget để re-resolve với config mới.
        $this->app->forgetInstance(MessagingRegistry::class);

        $this->tenant = Tenant::create(['name' => 'ShopeeTestTenant']);

        // ChannelAccount tạo cross-tenant (không có tenant context trong setUp)
        // → dùng withoutGlobalScope hoặc chỉ cần query()->create với tenant_id explicit.
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => '55',
            'shop_name' => 'Shopee Shop VN',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'access_token' => 'ACCESS_1',
        ]);
    }

    /**
     * Luồng chính: webhook event shopee_chat → job chạy sync →
     * conversation.provider phải là 'shopee_chat' (messaging code), KHÔNG phải 'shopee'.
     */
    public function test_inbound_shopee_chat_webhook_creates_conversation_with_messaging_provider_code(): void
    {
        // Tạo WebhookEvent giống MessagingWebhookIngestService lưu cho Shopee chat.
        $event = WebhookEvent::query()->create([
            'provider' => 'messaging.shopee_chat',
            'event_type' => MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            'external_id' => 'MSG_1',
            'external_shop_id' => '55',
            'status' => WebhookEvent::STATUS_PENDING,
            'signature_ok' => true,
            'headers' => [],
            'received_at' => now(),
            'attempts' => 0,
            'payload' => [
                'external_conversation_id' => 'CONV_1',
                'external_message_id' => 'MSG_1',
                'buyer_external_id' => 'BUYER_1',
                'body' => 'hi',
                'kind' => 'text',
            ],
        ]);

        // Chạy job đồng bộ (không fake queue)
        (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
        );

        // Conversation phải tồn tại với provider = 'shopee_chat' (messaging code),
        // KHÔNG phải 'shopee' (channel provider). Đây là assertion then chốt.
        $conv = Conversation::withoutGlobalScopes()
            ->where('channel_account_id', $this->account->id)
            ->where('external_conversation_id', 'CONV_1')
            ->first();

        $this->assertNotNull($conv, 'Conversation phải được tạo sau khi job chạy');
        $this->assertSame(
            'shopee_chat',
            $conv->provider,
            'conversation.provider phải là mã messaging connector (shopee_chat), không phải channel provider (shopee). '
            ."Bug: SendMessage/MessageController dùng conversation.provider để tra MessagingRegistry — nếu là 'shopee' sẽ không tìm thấy connector → reply thất bại."
        );

        // Xác nhận message inbound cũng được tạo
        $msg = Message::withoutGlobalScopes()
            ->where('conversation_id', $conv->id)
            ->where('external_message_id', 'MSG_1')
            ->first();

        $this->assertNotNull($msg, 'Message inbound phải được tạo sau khi ingest');
        $this->assertSame(Message::DIRECTION_INBOUND, $msg->direction);

        // WebhookEvent phải được đánh dấu processed
        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $event->fresh()->status);
    }

    /**
     * Replay idempotent: chạy lại job không tạo duplicate conversation/message.
     */
    public function test_inbound_shopee_chat_webhook_is_idempotent_on_replay(): void
    {
        $event = WebhookEvent::query()->create([
            'provider' => 'messaging.shopee_chat',
            'event_type' => MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            'external_id' => 'MSG_2',
            'external_shop_id' => '55',
            'status' => WebhookEvent::STATUS_PENDING,
            'signature_ok' => true,
            'headers' => [],
            'received_at' => now(),
            'attempts' => 0,
            'payload' => [
                'external_conversation_id' => 'CONV_2',
                'external_message_id' => 'MSG_2',
                'buyer_external_id' => 'BUYER_2',
                'body' => 'replay test',
                'kind' => 'text',
            ],
        ]);

        $registry = app(MessagingRegistry::class);
        $ingest = app(MessageIngestionService::class);

        (new ProcessMessagingWebhook($event->id))->handle($registry, $ingest);

        // Re-drive: reset status để job chạy lại
        $event->forceFill(['status' => WebhookEvent::STATUS_PENDING])->save();
        (new ProcessMessagingWebhook($event->id))->handle($registry, $ingest);

        $this->assertSame(
            1,
            Conversation::withoutGlobalScopes()->where('external_conversation_id', 'CONV_2')->count(),
            'Replay không được tạo conversation thứ hai'
        );
        $this->assertSame(
            1,
            Message::withoutGlobalScopes()->where('external_message_id', 'MSG_2')->count(),
            'Replay không được tạo message thứ hai'
        );
    }
}
