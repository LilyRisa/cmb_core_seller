<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeClient;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeWebhookVerifier;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * Contract test ShopeeChatConnector (SPEC-0024 / spec 2026-05-21). Shape-tested:
 * verify chữ ký push, parse code-10 webchat, send_message shape (Http::fake).
 * Live cần Shopee Seller Chat approval + sandbox (ngoài unit test).
 */
class ShopeeChatConnectorTest extends TestCase
{
    private function connector(): ShopeeChatConnector
    {
        ShopeeFixtures::configure();
        config([
            'integrations.shopee.push_url' => 'https://app.cmbcore.com/webhook/shopee',
            'integrations.shopee.endpoints.send_message' => '/api/v2/sellerchat/send_message',
            'integrations.shopee.chat_push_codes' => [10],
        ]);

        return new ShopeeChatConnector(
            (array) config('integrations.shopee'),
            new ShopeeWebhookVerifier,
            new ShopeeClient,
        );
    }

    private function signedPush(array $body): Request
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $pushUrl = 'https://app.cmbcore.com/webhook/shopee';
        $sign = hash_hmac('sha256', $pushUrl.'|'.$raw, 'PARTNER_KEY');
        $req = Request::create($pushUrl, 'POST', content: $raw);
        $req->headers->set('Authorization', $sign);

        return $req;
    }

    public function test_identity_and_capabilities(): void
    {
        $c = $this->connector();
        $this->assertSame('shopee_chat', $c->code());
        $this->assertTrue($c->supports('inbound.webhook'));
        $this->assertTrue($c->supports('outbound.text'));
        $this->assertTrue($c->supports('outbound.image'));
        $this->assertFalse($c->supports('outbound.video'));
    }

    public function test_oauth_methods_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->buildAuthorizationUrl('state');
    }

    public function test_verifies_valid_push_signature_and_rejects_bad(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['content' => ['conversation_id' => 'c1', 'message_id' => 'm1', 'from_id' => 'b1']])]);
        $this->assertTrue($this->connector()->verifyWebhookSignature($req));

        $bad = Request::create('https://app.cmbcore.com/webhook/shopee', 'POST', content: '{}');
        $bad->headers->set('Authorization', 'deadbeef');
        $this->assertFalse($this->connector()->verifyWebhookSignature($bad));
    }

    public function test_parses_code_10_webchat_message(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'type' => 'message',
            'content' => ['conversation_id' => 'CONV_1', 'message_id' => 'MSG_1', 'from_id' => 'BUYER_1', 'message_type' => 'text', 'content' => ['text' => 'Còn hàng không shop?'], 'created_timestamp' => 1700000001],
        ])]);

        $event = $this->connector()->parseWebhook($req);

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('shopee_chat', $event->provider);
        $this->assertSame('55', $event->externalShopId);
        $this->assertSame('CONV_1', $event->externalConversationId);
        $this->assertSame('MSG_1', $event->externalMessageId);
        $this->assertSame('BUYER_1', $event->buyerExternalId);
    }

    public function test_non_chat_code_is_unknown(): void
    {
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9'])]);
        $this->assertSame(MessagingWebhookEventDTO::TYPE_UNKNOWN, $this->connector()->parseWebhook($req)->type);
    }

    public function test_send_text_posts_correct_shape(): void
    {
        Http::fake(['*/api/v2/sellerchat/send_message*' => Http::response(['error' => '', 'response' => ['message_id' => 'OUT_1']], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $result = $this->connector()->sendText($auth, 'BUYER_1', 'Còn hàng nhé!');

        $this->assertSame('OUT_1', $result->externalMessageId);
        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/api/v2/sellerchat/send_message')
                && str_contains($request->url(), 'sign=')
                && ($data['to_id'] ?? null) === 'BUYER_1'
                && ($data['message_type'] ?? null) === 'text'
                && ($data['content']['text'] ?? null) === 'Còn hàng nhé!';
        });
    }

    public function test_send_image_posts_image_type(): void
    {
        Http::fake(['*/api/v2/sellerchat/send_message*' => Http::response(['error' => '', 'response' => ['message_id' => 'OUT_2']], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $media = new MediaRefDTO(kind: MessageKind::Image, mime: 'image/jpeg', externalUrl: 'https://cdn/x.jpg');
        $result = $this->connector()->sendMedia($auth, 'BUYER_1', $media);

        $this->assertSame('OUT_2', $result->externalMessageId);
        Http::assertSent(fn ($r) => ($r->data()['message_type'] ?? null) === 'image'
            && ($r->data()['content']['image_url'] ?? null) === 'https://cdn/x.jpg');
    }

    public function test_send_non_image_media_unsupported(): void
    {
        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $media = new MediaRefDTO(kind: MessageKind::Video, mime: 'video/mp4', externalUrl: 'https://cdn/v.mp4');

        $this->expectException(UnsupportedOperation::class);
        $this->connector()->sendMedia($auth, 'BUYER_1', $media);
    }
}
