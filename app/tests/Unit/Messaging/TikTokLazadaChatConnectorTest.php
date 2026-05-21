<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Lazada\LazadaChatConnector;
use CMBcoreSeller\Integrations\Messaging\TikTok\TikTokChatConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contract test TikTok + Lazada chat connectors (SPEC-0024 S4/S8). Verify chữ ký
 * (tái dùng scheme Channels), parseWebhook, shape send (Http::fake). Live cần
 * API approval + sandbox.
 */
class TikTokLazadaChatConnectorTest extends TestCase
{
    private function req(string $body, array $server = []): Request
    {
        return Request::create('/webhook/messaging/x', 'POST', [], [], [], $server, $body);
    }

    // --- TikTok -----------------------------------------------------------

    public function test_tiktok_verifies_signature(): void
    {
        config(['integrations.tiktok.app_key' => 'AK', 'integrations.tiktok.app_secret' => 'SECRET']);
        $body = '{"type":1,"shop_id":"s1"}';
        $sig = hash_hmac('sha256', 'AK'.$body, 'SECRET');

        $c = new TikTokChatConnector;
        $this->assertTrue($c->verifyWebhookSignature($this->req($body, ['HTTP_AUTHORIZATION' => $sig])));
        $this->assertFalse($c->verifyWebhookSignature($this->req($body, ['HTTP_AUTHORIZATION' => 'wrong'])));
    }

    public function test_tiktok_parses_message_webhook(): void
    {
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => ['conversation_id' => 'CONV_1', 'message_id' => 'MSG_1', 'sender' => ['im_user_id' => 'BUYER_1']],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('CONV_1', $event->externalConversationId);
        $this->assertSame('MSG_1', $event->externalMessageId);
        $this->assertSame('BUYER_1', $event->buyerExternalId);
    }

    // --- Phase B: normalized kind/body/attachments parsing -------------------

    public function test_tiktok_parses_text_message_with_body(): void
    {
        // TikTok doc: data.type="TEXT", data.content=JSON string {"content":"simple text"}
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_TXT',
                'message_id' => 'MSG_TXT',
                'sender' => ['im_user_id' => 'BUYER_1'],
                'type' => 'TEXT',
                'content' => json_encode(['content' => 'Hello seller!']),
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame(MessageKind::Text, $event->kind);
        $this->assertSame('Hello seller!', $event->body);
        $this->assertSame([], $event->attachments);
    }

    public function test_tiktok_parses_image_message_with_attachment(): void
    {
        // TikTok doc: data.type="IMAGE", data.content=JSON string {"url":"...","width":"304","height":"290"}
        $imageContent = json_encode([
            'url' => 'https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53n.jpg',
            'width' => '304',
            'height' => '290',
        ]);
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_IMG',
                'message_id' => 'MSG_IMG',
                'sender' => ['im_user_id' => 'BUYER_IMG'],
                'type' => 'IMAGE',
                'content' => $imageContent,
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));

        $this->assertSame(MessageKind::Image, $event->kind);
        $this->assertNull($event->body);
        $this->assertCount(1, $event->attachments);

        /** @var MediaRefDTO $att */
        $att = $event->attachments[0];
        $this->assertInstanceOf(MediaRefDTO::class, $att);
        $this->assertSame(MessageKind::Image, $att->kind);
        $this->assertSame('https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53n.jpg', $att->externalUrl);
        $this->assertSame(304, $att->width);
        $this->assertSame(290, $att->height);
    }

    public function test_tiktok_parses_video_message_with_attachment(): void
    {
        // TikTok doc: data.type="VIDEO", data.content=JSON string with url + duration (string seconds)
        $videoContent = json_encode([
            'url' => 'https://video-boei18n.byted.org/storage/v1/tos-boei18n/abc.mp4',
            'cover' => 'https://p-boei18n.byted.org/cover.jpeg',
            'width' => 640,
            'height' => 360,
            'duration' => '20.504',  // string seconds per doc — verify sandbox
            'format' => 'mp4',
        ]);
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_VID',
                'message_id' => 'MSG_VID',
                'sender' => ['im_user_id' => 'BUYER_VID'],
                'type' => 'VIDEO',
                'content' => $videoContent,
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));

        $this->assertSame(MessageKind::Video, $event->kind);
        $this->assertNull($event->body);
        $this->assertCount(1, $event->attachments);

        /** @var MediaRefDTO $att */
        $att = $event->attachments[0];
        $this->assertSame(MessageKind::Video, $att->kind);
        $this->assertSame('https://video-boei18n.byted.org/storage/v1/tos-boei18n/abc.mp4', $att->externalUrl);
        $this->assertSame(20504, $att->durationMs);
        $this->assertSame(640, $att->width);
        $this->assertSame(360, $att->height);
    }

    public function test_tiktok_parses_card_type_as_text_label(): void
    {
        // PRODUCT_CARD, ORDER_CARD, etc. → body = '[TYPE]'
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_CARD',
                'message_id' => 'MSG_CARD',
                'sender' => ['im_user_id' => 'BUYER_CARD'],
                'type' => 'PRODUCT_CARD',
                'content' => json_encode(['product_id' => '12345']),
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));

        $this->assertSame(MessageKind::Text, $event->kind);
        $this->assertSame('[PRODUCT_CARD]', $event->body);
        $this->assertSame([], $event->attachments);
    }

    public function test_tiktok_message_without_type_has_null_kind(): void
    {
        // No conversation/message IDs → TYPE_UNKNOWN, kind=null.
        $body = json_encode(['shop_id' => 'SHOP_1', 'timestamp' => 1716200000, 'data' => []]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_UNKNOWN, $event->type);
        $this->assertNull($event->kind);
        $this->assertNull($event->body);
        $this->assertSame([], $event->attachments);
    }

    public function test_tiktok_send_text_posts(): void
    {
        config(['integrations.tiktok.app_key' => 'AK', 'integrations.tiktok.app_secret' => 'SECRET', 'integrations.tiktok.base_url' => 'https://open-api.tiktokglobalshop.com']);
        Http::fake(['open-api.tiktokglobalshop.com/*' => Http::response(['code' => 0, 'data' => ['message_id' => 'TT_OUT']], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'tiktok_chat', externalShopId: 'SHOP_1', accessToken: 'TOK', extra: ['shop_cipher' => 'CIPHER']);
        $result = (new TikTokChatConnector)->sendText($auth, 'CONV_1', 'Xin chào');

        $this->assertSame('TT_OUT', $result->externalMessageId);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/customer_service/202309/conversations/CONV_1/messages')
            && $r->hasHeader('x-tts-access-token', 'TOK'));
    }

    // --- Lazada -----------------------------------------------------------

    public function test_lazada_verifies_header_signature(): void
    {
        config(['integrations.lazada.app_secret' => 'LZSEC']);
        $body = '{"message_type":1}';
        $sig = hash_hmac('sha256', $body, 'LZSEC');

        $c = new LazadaChatConnector;
        $this->assertTrue($c->verifyWebhookSignature($this->req($body, ['HTTP_X_LAZOP_SIGN' => $sig])));
        $this->assertFalse($c->verifyWebhookSignature($this->req($body, ['HTTP_X_LAZOP_SIGN' => 'nope'])));
    }

    public function test_lazada_parses_message_webhook(): void
    {
        $body = json_encode([
            'timestamp' => 1716200000000,
            'data' => ['session_id' => 'SESS_1', 'message_id' => 'LM_1', 'from_account_id' => 'B1', 'seller_id' => 'SELLER_1'],
        ]);

        $event = (new LazadaChatConnector)->parseWebhook($this->req($body));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('SESS_1', $event->externalConversationId);
        $this->assertSame('LM_1', $event->externalMessageId);
    }

    public function test_lazada_send_text_posts(): void
    {
        config(['integrations.lazada.app_key' => 'LK', 'integrations.lazada.app_secret' => 'LZSEC', 'integrations.lazada.base_url' => 'https://api.lazada.vn/rest']);
        Http::fake(['api.lazada.vn/*' => Http::response(['code' => '0', 'data' => ['message_id' => 'LZ_OUT']], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'lazada_chat', externalShopId: 'SELLER_1', accessToken: 'TOK');
        $result = (new LazadaChatConnector)->sendText($auth, 'SESS_1', 'Xin chào');

        $this->assertSame('LZ_OUT', $result->externalMessageId);
        Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), '/im/message/send')
                && ($d['template_id'] ?? null) === '1'   // text template
                && ($d['session_id'] ?? null) === 'SESS_1'
                && ($d['txt'] ?? null) === 'Xin chào'
                && ! empty($d['sign']);
        });
    }
}
