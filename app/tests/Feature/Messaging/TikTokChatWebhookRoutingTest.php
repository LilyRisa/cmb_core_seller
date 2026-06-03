<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Demux push TikTok tại /webhook/tiktok: tin nhắn CS (type ∈ chat_push_types =
 * 13/14/33) → pipeline messaging (tiktok_chat); đơn hàng (type 1, ...) → pipeline
 * Channels — không hồi quy. Mirror ShopeeChatWebhookRoutingTest.
 *
 * Regression then chốt: tin nhắn buyer (type 14) KHÔNG bao giờ rơi vào pipeline đơn
 * (trước đây map `14 → shop_deauthorized` ⇒ tin buyer revoke gian hàng).
 */
class TikTokChatWebhookRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.tiktok.app_key' => 'AK',
            'integrations.tiktok.app_secret' => 'SECRET',
            'integrations.tiktok.chat_push_types' => [13, 14, 33],
            'integrations.channels' => ['manual', 'tiktok'],
            'integrations.messaging' => ['tiktok_chat'],
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
        $this->app->forgetInstance(ChannelRegistry::class);
    }

    private function postPush(array $body): TestResponse
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $sign = hash_hmac('sha256', 'AK'.$raw, 'SECRET');

        return $this->call('POST', '/webhook/tiktok', [], [], [],
            ['HTTP_AUTHORIZATION' => $sign, 'CONTENT_TYPE' => 'application/json'], $raw);
    }

    public function test_new_message_type_14_routes_to_messaging(): void
    {
        Queue::fake();

        $this->postPush([
            'type' => 14, 'shop_id' => 'SHOP_1', 'timestamp' => 1700000000,
            'data' => [
                'conversation_id' => 'CONV_1', 'message_id' => 'MSG_1',
                'sender' => ['im_user_id' => 'BUYER_1', 'role' => 'BUYER'],
                'type' => 'TEXT', 'content' => json_encode(['content' => 'hi']),
            ],
        ])->assertOk();

        $this->assertSame(1, WebhookEvent::query()->where('provider', 'messaging.tiktok_chat')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'tiktok')->count());
        // Bug nguy hiểm: KHÔNG được tạo event shop_deauthorized từ một tin nhắn.
        $this->assertSame(0, WebhookEvent::query()->where('event_type', 'shop_deauthorized')->count());
        Queue::assertPushed(ProcessMessagingWebhook::class, 1);
        Queue::assertNotPushed(ProcessWebhookEvent::class);
    }

    public function test_order_type_1_routes_to_channels(): void
    {
        Queue::fake();

        $this->postPush([
            'type' => 1, 'shop_id' => 'SHOP_1', 'timestamp' => 1700000000,
            'data' => ['order_id' => 'ORD_9', 'order_status' => 'AWAITING_SHIPMENT'],
        ])->assertOk();

        $this->assertSame(1, WebhookEvent::query()->where('provider', 'tiktok')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'messaging.tiktok_chat')->count());
        Queue::assertPushed(ProcessWebhookEvent::class, 1);
        Queue::assertNotPushed(ProcessMessagingWebhook::class);
    }

    public function test_bad_signature_rejected(): void
    {
        $raw = json_encode(['type' => 14, 'shop_id' => 'SHOP_1'], JSON_UNESCAPED_SLASHES);

        $this->call('POST', '/webhook/tiktok', [], [], [],
            ['HTTP_AUTHORIZATION' => 'deadbeef', 'CONTENT_TYPE' => 'application/json'], $raw)
            ->assertStatus(401);
    }

    public function test_chat_push_when_connector_disabled_is_acked_without_storing(): void
    {
        Queue::fake();
        config(['integrations.messaging' => []]); // tiktok_chat KHÔNG đăng ký
        $this->app->forgetInstance(MessagingRegistry::class);

        $this->postPush([
            'type' => 14, 'shop_id' => 'SHOP_1', 'timestamp' => 1700000000,
            'data' => ['conversation_id' => 'C1', 'message_id' => 'M1', 'sender' => ['im_user_id' => 'B1', 'role' => 'BUYER']],
        ])->assertOk()->assertJsonPath('note', 'chat_connector_disabled');

        $this->assertSame(0, WebhookEvent::query()->where('provider', 'tiktok')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'messaging.tiktok_chat')->count());
    }
}
