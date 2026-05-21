<?php

namespace Tests\Unit\Messaging;

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

    public function test_send_template_unsupported(): void
    {
        Http::preventStrayRequests(); // template không được gửi HTTP — nếu sau này đổi hành vi, test fail to.
        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');

        $this->expectException(UnsupportedOperation::class);
        $this->connector()->sendTemplate($auth, 'BUYER_1', 'tpl_code', ['_resolved_body' => 'hi']);
    }

    // --- Phase B: normalized kind/body/attachments parsing -------------------

    public function test_parses_code_10_text_push_with_kind_and_body(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'type' => 'message',
            'content' => [
                'conversation_id' => 'CONV_T',
                'message_id' => 'MSG_T',
                'from_id' => 'BUYER_T',
                'message_type' => 'text',
                'content' => ['text' => 'Còn hàng không shop?'],
                'created_timestamp' => 1700000001,
            ],
        ])]);

        $event = $this->connector()->parseWebhook($req);

        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Text, $event->kind);
        $this->assertSame('Còn hàng không shop?', $event->body);
        $this->assertSame([], $event->attachments);
    }

    public function test_parses_code_10_image_push_with_attachment(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'type' => 'message',
            'content' => [
                'conversation_id' => 'CONV_I',
                'message_id' => 'MSG_I',
                'from_id' => 'BUYER_I',
                'message_type' => 'image',
                'content' => [
                    'url' => 'https://cf.shopee.vn/file/09591ecdc9f1dc7bd507817797d826fe_dynamic',
                    'thumb_url' => 'b9591ecdc9f1dc7bd507817797d826fe_dynamic_tn',
                    'thumb_height' => 711,
                    'thumb_width' => 400,
                    'file_server_id' => 0,
                ],
                'created_timestamp' => 1700000002,
            ],
        ])]);

        $event = $this->connector()->parseWebhook($req);

        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Image, $event->kind);
        $this->assertNull($event->body);
        $this->assertCount(1, $event->attachments);

        $att = $event->attachments[0];
        $this->assertInstanceOf(\CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO::class, $att);
        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Image, $att->kind);
        $this->assertSame('https://cf.shopee.vn/file/09591ecdc9f1dc7bd507817797d826fe_dynamic', $att->externalUrl);
        $this->assertSame(400, $att->width);
        $this->assertSame(711, $att->height);
    }

    public function test_parses_code_10_video_push_with_attachment(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'type' => 'message',
            'content' => [
                'conversation_id' => 'CONV_V',
                'message_id' => 'MSG_V',
                'from_id' => 'BUYER_V',
                'message_type' => 'video',
                'content' => [
                    'video_url' => 'cf03c9e1fe2c0992cdb51c3cb6eab2bd',
                    'thumb_url' => '6c710d7679c9f3a9a7287250421d17d3_dynamic_tn',
                    'thumb_width' => 399,
                    'thumb_height' => 713,
                    'duration_seconds' => 15,
                ],
                'created_timestamp' => 1700000003,
            ],
        ])]);

        $event = $this->connector()->parseWebhook($req);

        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Video, $event->kind);
        $this->assertNull($event->body);
        $this->assertCount(1, $event->attachments);

        $att = $event->attachments[0];
        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Video, $att->kind);
        $this->assertSame('cf03c9e1fe2c0992cdb51c3cb6eab2bd', $att->externalUrl);
        $this->assertSame(15000, $att->durationMs);
        $this->assertSame(399, $att->width);
        $this->assertSame(713, $att->height);
    }

    public function test_parses_code_10_item_push_as_text_with_item_body(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'type' => 'message',
            'content' => [
                'conversation_id' => 'CONV_ITEM',
                'message_id' => 'MSG_ITEM',
                'from_id' => 'BUYER_ITEM',
                'message_type' => 'item',
                'content' => ['shop_id' => 123456789, 'item_id' => 9112503530],
                'created_timestamp' => 1700000004,
            ],
        ])]);

        $event = $this->connector()->parseWebhook($req);

        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Text, $event->kind);
        $this->assertStringContainsString('9112503530', $event->body ?? '');
        $this->assertSame([], $event->attachments);
    }

    public function test_non_chat_code_has_null_kind_and_no_attachments(): void
    {
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9'])]);
        $event = $this->connector()->parseWebhook($req);

        $this->assertNull($event->kind);
        $this->assertNull($event->body);
        $this->assertSame([], $event->attachments);
    }

    public function test_registry_resolves_shopee_chat_when_enabled(): void
    {
        ShopeeFixtures::configure();
        config(['integrations.messaging' => ['shopee_chat']]);
        $this->app->forgetInstance(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class);

        $registry = $this->app->make(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class);
        $this->assertTrue($registry->has('shopee_chat'));
        $this->assertInstanceOf(ShopeeChatConnector::class, $registry->for('shopee_chat'));
    }
}
