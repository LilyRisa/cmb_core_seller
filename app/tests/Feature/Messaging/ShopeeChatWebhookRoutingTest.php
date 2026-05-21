<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * Demux push Shopee tại /webhook/shopee: chat (code 10) → pipeline messaging
 * (shopee_chat); đơn hàng (code 3) → pipeline Channels — không hồi quy.
 */
class ShopeeChatWebhookRoutingTest extends TestCase
{
    use RefreshDatabase;

    private const PUSH_URL = 'https://app.cmbcore.com/webhook/shopee';

    protected function setUp(): void
    {
        parent::setUp();
        ShopeeFixtures::configure();
        config([
            'integrations.shopee.push_url' => self::PUSH_URL,
            'integrations.shopee.chat_push_codes' => [10],
            'integrations.channels' => ['manual', 'shopee'],
            'integrations.messaging' => ['shopee_chat'],
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
        $this->app->forgetInstance(\CMBcoreSeller\Integrations\Channels\ChannelRegistry::class);
    }

    private function postPush(array $body): \Illuminate\Testing\TestResponse
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $sign = hash_hmac('sha256', self::PUSH_URL.'|'.$raw, 'PARTNER_KEY');

        return $this->call('POST', '/webhook/shopee', [], [], [],
            ['HTTP_AUTHORIZATION' => $sign, 'CONTENT_TYPE' => 'application/json'], $raw);
    }

    public function test_chat_push_routes_to_messaging(): void
    {
        Queue::fake();

        $this->postPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'content' => ['conversation_id' => 'CONV_1', 'message_id' => 'MSG_1', 'from_id' => 'BUYER_1', 'message_type' => 'text', 'content' => ['text' => 'hi']],
        ])])->assertOk();

        $this->assertSame(1, WebhookEvent::query()->where('provider', 'messaging.shopee_chat')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'shopee')->count());
        Queue::assertPushed(ProcessMessagingWebhook::class, 1);
    }

    public function test_order_push_routes_to_channels(): void
    {
        Queue::fake();

        $this->postPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])])
            ->assertOk();

        $this->assertSame(1, WebhookEvent::query()->where('provider', 'shopee')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'messaging.shopee_chat')->count());
        Queue::assertPushed(ProcessWebhookEvent::class, 1);
    }

    public function test_bad_signature_rejected(): void
    {
        $raw = json_encode(['code' => 10, 'shop_id' => 55], JSON_UNESCAPED_SLASHES);

        $this->call('POST', '/webhook/shopee', [], [], [],
            ['HTTP_AUTHORIZATION' => 'deadbeef', 'CONTENT_TYPE' => 'application/json'], $raw)
            ->assertStatus(401);
    }

    public function test_chat_push_when_connector_disabled_is_acked_without_storing(): void
    {
        Queue::fake();
        config(['integrations.messaging' => []]); // shopee_chat KHÔNG đăng ký
        $this->app->forgetInstance(MessagingRegistry::class);

        $this->postPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'content' => ['conversation_id' => 'C1', 'message_id' => 'M1', 'from_id' => 'B1'],
        ])])->assertOk()->assertJsonPath('note', 'chat_connector_disabled');

        $this->assertSame(0, WebhookEvent::query()->where('provider', 'shopee')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'messaging.shopee_chat')->count());
    }
}
