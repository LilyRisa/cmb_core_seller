<?php

namespace Tests\Unit\Messaging;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\ConversationDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
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

    public function test_tiktok_parses_type_33_creator_message(): void
    {
        // Type 33 (new message listener) khác type 14: dùng `msg_type` (không phải `type`),
        // sender qua `sender.sender_im_user_id` (không phải `im_user_id`). Doc docv2_page_33.
        $body = json_encode([
            'type' => 33, 'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_33',
                'message_id' => 'MSG_33',
                'msg_type' => 'TEXT',
                'content' => json_encode(['content' => 'Hi from creator']),
                'sender' => ['sender_im_user_id' => 'CREATOR_1'],
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('CONV_33', $event->externalConversationId);
        $this->assertSame('MSG_33', $event->externalMessageId);
        $this->assertSame('CREATOR_1', $event->buyerExternalId);
        $this->assertSame(MessageKind::Text, $event->kind);
        $this->assertSame('Hi from creator', $event->body);
    }

    public function test_tiktok_parses_type_33_raw_string_content(): void
    {
        // Event example của doc type 33 cho content là chuỗi thô "Hello" (không bọc JSON).
        $body = json_encode([
            'type' => 33, 'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_33B',
                'message_id' => 'MSG_33B',
                'msg_type' => 'TEXT',
                'content' => 'Hello',
                'sender' => ['sender_im_user_id' => 'CREATOR_2'],
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('Hello', $event->body);
    }

    // --- Phase 1: echo / own-message guard -----------------------------------

    /**
     * @dataProvider tiktokNonBuyerRoleProvider
     */
    public function test_tiktok_non_buyer_role_maps_to_unknown(string $role): void
    {
        // Per TikTok CS API overview: sender.role = BUYER | SHOP | CUSTOMER_SERVICE | SYSTEM | ROBOT.
        // Messages sent by the shop/agent/system must not be ingested as inbound buyer messages.
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_ECHO',
                'message_id' => 'MSG_ECHO',
                'sender' => ['im_user_id' => 'SHOP_IM_ID', 'role' => $role],
                'type' => 'TEXT',
                'content' => json_encode(['content' => 'Auto-reply từ shop']),
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));
        $this->assertSame(
            MessagingWebhookEventDTO::TYPE_UNKNOWN,
            $event->type,
            "Sender role '{$role}' must map to TYPE_UNKNOWN (not be ingested as inbound)",
        );
    }

    /** @return array<string, array{string}> */
    public static function tiktokNonBuyerRoleProvider(): array
    {
        return [
            'SHOP' => ['SHOP'],
            'CUSTOMER_SERVICE' => ['CUSTOMER_SERVICE'],
            'SYSTEM' => ['SYSTEM'],
            'ROBOT' => ['ROBOT'],
        ];
    }

    public function test_tiktok_buyer_role_still_maps_to_message_received(): void
    {
        // Ensure the echo guard does NOT drop genuine buyer messages (regression check).
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => [
                'conversation_id' => 'CONV_B',
                'message_id' => 'MSG_B',
                'sender' => ['im_user_id' => 'BUYER_IM_ID', 'role' => 'BUYER'],
                'type' => 'TEXT',
                'content' => json_encode(['content' => 'Còn hàng không?']),
            ],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('BUYER_IM_ID', $event->buyerExternalId);
    }

    public function test_tiktok_absent_role_still_maps_to_message_received(): void
    {
        // When sender.role is absent (old webhook or sandbox), default to ingest (no false drop).
        $body = json_encode([
            'shop_id' => 'SHOP_1', 'timestamp' => 1716200000,
            'data' => ['conversation_id' => 'CONV_1', 'message_id' => 'MSG_1', 'sender' => ['im_user_id' => 'BUYER_1']],
        ]);

        $event = (new TikTokChatConnector)->parseWebhook($this->req($body));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
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

    public function test_tiktok_send_image_uploads_then_sends(): void
    {
        // Phase D: upload-first flow — fetch bytes from our signed URL, upload to TikTok CDN via
        // POST /customer_service/202309/images/upload (multipart field `data`; returns data.url,
        // data.width, data.height), then send IMAGE with the CDN url.
        // Per official doc docv2_page_upload-buyer-messages-image-202309.md.
        config(['integrations.tiktok.app_key' => 'AK', 'integrations.tiktok.app_secret' => 'SECRET', 'integrations.tiktok.base_url' => 'https://open-api.tiktokglobalshop.com']);

        Http::fake([
            // Byte fetch from our signed storage URL.
            'our-signed.internal/*' => Http::response('BYTES', 200),
            // TikTok image upload endpoint → CDN url with dimensions.
            'open-api.tiktokglobalshop.com/customer_service/202309/images/upload*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => ['url' => 'https://tt-cdn/y.jpg', 'width' => 100, 'height' => 100],
            ], 200),
            // TikTok IM send → message_id.
            'open-api.tiktokglobalshop.com/customer_service/202309/conversations/*' => Http::response([
                'code' => 0,
                'data' => ['message_id' => 'TT_IMG_OUT'],
            ], 200),
        ]);

        $media = new MediaRefDTO(
            kind: MessageKind::Image,
            mime: 'image/jpeg',
            externalUrl: 'https://our-signed.internal/y.jpg',
            width: 100,
            height: 100,
        );

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'tiktok_chat', externalShopId: 'SHOP_1', accessToken: 'TOK', extra: ['shop_cipher' => 'CIPHER']);
        $result = (new TikTokChatConnector)->sendMedia($auth, 'CONV_1', $media);

        $this->assertSame('TT_IMG_OUT', $result->externalMessageId);

        // Assert byte-fetch was sent to our signed storage URL.
        Http::assertSent(fn ($r) => str_contains($r->url(), 'our-signed.internal'));

        // Assert upload was called at /images/upload with multipart and access token header.
        Http::assertSent(fn ($r) => str_contains($r->url(), '/customer_service/202309/images/upload')
            && $r->hasHeader('x-tts-access-token', 'TOK')
        );

        // Assert send used the CDN url (not the original externalUrl) as IMAGE type.
        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), '/customer_service/202309/conversations/CONV_1/messages')) {
                return false;
            }
            $payload = json_decode($r->body(), true);
            if (($payload['type'] ?? null) !== 'IMAGE') {
                return false;
            }
            $content = json_decode($payload['content'] ?? '{}', true);

            return ($content['url'] ?? null) === 'https://tt-cdn/y.jpg';  // UPLOADED CDN url
        });
    }

    public function test_tiktok_send_video_unsupported(): void
    {
        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'tiktok_chat', externalShopId: 'SHOP_1', accessToken: 'TOK', extra: ['shop_cipher' => 'CIPHER']);
        $media = new MediaRefDTO(kind: MessageKind::Video, mime: 'video/mp4', externalUrl: 'https://cdn/v.mp4');

        $this->expectException(UnsupportedOperation::class);
        (new TikTokChatConnector)->sendMedia($auth, 'CONV_1', $media);
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
        config(['integrations.lazada.app_key' => 'LK', 'integrations.lazada.app_secret' => 'LZSEC', 'integrations.lazada.api_base_url' => 'https://api.lazada.vn/rest']);
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
                && ! empty($d['sign'])
                && ! empty($d['partner_id']);   // mandatory system param
        });
    }

    public function test_lazada_send_image_uploads_then_sends(): void
    {
        // Phase D: upload-first flow — fetch bytes from our signed URL, upload to Lazada CDN
        // via /image/upload (param `image` = binary stream; returns data.image.url), then send
        // with template_id=3 and the CDN url. Per official doc apps_doc_api_path_2Fimage_2Fupload.md.
        config(['integrations.lazada.app_key' => 'LK', 'integrations.lazada.app_secret' => 'LZSEC', 'integrations.lazada.api_base_url' => 'https://api.lazada.vn/rest']);

        Http::fake([
            // Byte fetch from our signed storage URL.
            'our-signed.internal/*' => Http::response('BYTES', 200),
            // Lazada image upload endpoint → CDN url.
            'api.lazada.vn/rest/image/upload*' => Http::response([
                'code' => '0',
                'data' => ['image' => ['url' => 'https://lzd-cdn/x.jpg', 'hash_code' => 'abc']],
            ], 200),
            // Lazada IM send → message_id.
            'api.lazada.vn/rest/im/message/send*' => Http::response(['code' => '0', 'data' => ['message_id' => 'LZ_IMG_OUT']], 200),
        ]);

        $media = new MediaRefDTO(
            kind: MessageKind::Image,
            mime: 'image/jpeg',
            externalUrl: 'https://our-signed.internal/photo.jpg',
            width: 800,
            height: 600,
        );

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'lazada_chat', externalShopId: 'SELLER_1', accessToken: 'TOK');
        $result = (new LazadaChatConnector)->sendMedia($auth, 'SESS_1', $media);

        $this->assertSame('LZ_IMG_OUT', $result->externalMessageId);

        // Assert byte-fetch was sent to our signed storage URL.
        Http::assertSent(fn ($r) => str_contains($r->url(), 'our-signed.internal'));

        // Assert upload was called at /image/upload.
        Http::assertSent(fn ($r) => str_contains($r->url(), '/image/upload')
            && ! empty($r->url())  // upload endpoint hit
        );

        // Assert send used the CDN url (not the original externalUrl) with template_id=3.
        Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), '/im/message/send')
                && ($d['template_id'] ?? null) === '3'
                && ($d['img_url'] ?? null) === 'https://lzd-cdn/x.jpg'   // UPLOADED CDN url
                && ($d['session_id'] ?? null) === 'SESS_1'
                && ! empty($d['sign'])
                && ! empty($d['partner_id']);
        });
    }

    public function test_lazada_send_video_unsupported(): void
    {
        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'lazada_chat', externalShopId: 'SELLER_1', accessToken: 'TOK');
        $media = new MediaRefDTO(kind: MessageKind::Video, mime: 'video/mp4', externalUrl: 'https://cdn/v.mp4');

        $this->expectException(UnsupportedOperation::class);
        (new LazadaChatConnector)->sendMedia($auth, 'SESS_1', $media);
    }

    // --- Lazada IM polling (Phase C2) ----------------------------------------

    public function test_lazada_inbound_polling_capability_true(): void
    {
        $caps = (new LazadaChatConnector)->capabilities();
        $this->assertTrue($caps['inbound.polling']);
    }

    public function test_lazada_inbound_webhook_false(): void
    {
        // Lazada IM has NO push webhook — polling is the only inbound path.
        $caps = (new LazadaChatConnector)->capabilities();
        $this->assertFalse($caps['inbound.webhook'], 'inbound.webhook must be false — Lazada IM has no webhook push');
        $this->assertTrue($caps['inbound.polling'], 'inbound.polling must be true');
    }

    public function test_lazada_fetch_conversations_parses_sessions(): void
    {
        config(['integrations.lazada' => [
            'app_key' => 'K',
            'app_secret' => 'S',
            'api_base_url' => 'https://api.lazada.vn/rest',
        ]]);

        $responsePayload = [
            'code' => '0',
            'success' => 'true',
            'err_code' => '0',
            'err_message' => 'SUCCESS',
            'data' => [
                'session_list' => [
                    [
                        'session_id' => 'SESS_A',
                        'buyer_id' => '111111',
                        'title' => 'Buyer Alpha',
                        'head_url' => 'https://example.com/alpha.jpg',
                        'summary' => 'Hello!',
                        'unread_count' => '3',
                        'last_message_time' => '1716200000000',
                        'last_message_id' => 'MSG_A1',
                        'site_id' => 'VN',
                        'tags' => [],
                    ],
                    [
                        'session_id' => 'SESS_B',
                        'buyer_id' => '222222',
                        'title' => 'Buyer Beta',
                        'head_url' => '',
                        'summary' => 'Còn hàng không?',
                        'unread_count' => '0',
                        'last_message_time' => '1716100000000',
                        'last_message_id' => 'MSG_B1',
                        'site_id' => 'VN',
                        'tags' => [],
                    ],
                ],
                'has_more' => 'true',
                'next_start_time' => '1716100000000',
                'last_session_id' => 'SESS_B',
            ],
        ];

        Http::fake(['api.lazada.vn/*' => Http::response($responsePayload, 200)]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1,
            provider: 'lazada_chat',
            externalShopId: 'SELLER1',
            accessToken: 'TOKEN'
        );

        $page = (new LazadaChatConnector)->fetchConversations($auth);

        // Assert 2 ConversationDTOs parsed correctly
        $this->assertCount(2, $page->items);

        /** @var ConversationDTO $first */
        $first = $page->items[0];
        $this->assertSame('SESS_A', $first->externalConversationId);
        $this->assertSame('111111', $first->buyerExternalId);
        $this->assertSame('Buyer Alpha', $first->buyerName);
        $this->assertSame('https://example.com/alpha.jpg', $first->buyerAvatarUrl);
        $this->assertSame('Hello!', $first->lastMessagePreview);
        $this->assertSame(3, $first->unreadCount);
        $this->assertNotNull($first->lastMessageAt);

        /** @var ConversationDTO $second */
        $second = $page->items[1];
        $this->assertSame('SESS_B', $second->externalConversationId);
        $this->assertSame('222222', $second->buyerExternalId);
        $this->assertNull($second->buyerAvatarUrl);  // empty head_url → null
        $this->assertSame(0, $second->unreadCount);

        // Assert pagination
        $this->assertTrue($page->hasMore);
        $this->assertNotNull($page->nextCursor);
        // cursor encodes "last_session_id|next_start_time"
        $this->assertStringContainsString('SESS_B', $page->nextCursor);
        $this->assertStringContainsString('1716100000000', $page->nextCursor);

        // Assert the GET request was sent to the correct endpoint with sign and mandatory partner_id.
        Http::assertSent(function ($r) {
            return str_contains($r->url(), '/im/session/list')
                && str_contains($r->url(), 'sign=')
                && str_contains($r->url(), 'app_key=K')
                && str_contains($r->url(), 'access_token=TOKEN')
                && str_contains($r->url(), 'partner_id=');
        });
    }

    public function test_lazada_fetch_messages_parses_text_and_image(): void
    {
        config(['integrations.lazada' => [
            'app_key' => 'K',
            'app_secret' => 'S',
            'api_base_url' => 'https://api.lazada.vn/rest',
        ]]);

        // Text message: template_id=1, from_account_type=1 (buyer → Inbound)
        $textContent = json_encode(['txt' => 'Bao giờ ship?', 'activeContent' => []]);
        // Image message: template_id=3, from_account_type=2 (seller → Outbound)
        $imageContent = json_encode(['img_url' => 'https://img.lazada.vn/photo.jpg', 'width' => 800, 'height' => 600]);

        $responsePayload = [
            'code' => '0',
            'success' => 'true',
            'err_code' => '0',
            'err_message' => 'null',
            'data' => [
                'message_list' => [
                    [
                        'message_id' => 'MSG_TEXT_1',
                        'session_id' => 'SESS_X',
                        'template_id' => '1',
                        'from_account_id' => '999001',
                        'from_account_type' => '1',  // buyer
                        'to_account_id' => '100001',
                        'to_account_type' => '2',
                        'content' => $textContent,
                        'send_time' => '1716200000000',
                        'type' => '1',
                        'status' => '0',
                        'auto_reply' => 'false',
                        'site_id' => 'VN',
                    ],
                    [
                        'message_id' => 'MSG_IMAGE_1',
                        'session_id' => 'SESS_X',
                        'template_id' => '3',
                        'from_account_id' => '100001',
                        'from_account_type' => '2',  // seller
                        'to_account_id' => '999001',
                        'to_account_type' => '1',
                        'content' => $imageContent,
                        'send_time' => '1716200005000',
                        'type' => '1',
                        'status' => '0',
                        'auto_reply' => 'false',
                        'site_id' => 'VN',
                    ],
                ],
                'has_more' => 'false',
                'next_start_time' => '1716200005000',
                'last_message_id' => 'MSG_IMAGE_1',
            ],
        ];

        Http::fake(['api.lazada.vn/*' => Http::response($responsePayload, 200)]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1,
            provider: 'lazada_chat',
            externalShopId: 'SELLER1',
            accessToken: 'TOKEN'
        );

        $page = (new LazadaChatConnector)->fetchMessages($auth, 'SESS_X');

        $this->assertCount(2, $page->items);

        /** @var MessageDTO $textMsg */
        $textMsg = $page->items[0];
        $this->assertSame('MSG_TEXT_1', $textMsg->externalMessageId);
        $this->assertSame('SESS_X', $textMsg->externalConversationId);
        $this->assertSame(MessageKind::Text, $textMsg->kind);
        $this->assertSame('Bao giờ ship?', $textMsg->body);
        $this->assertSame([], $textMsg->attachments);
        $this->assertSame(MessageDirection::Inbound, $textMsg->direction);
        $this->assertSame('999001', $textMsg->buyerExternalId);
        $this->assertNotNull($textMsg->sentAt);

        /** @var MessageDTO $imgMsg */
        $imgMsg = $page->items[1];
        $this->assertSame('MSG_IMAGE_1', $imgMsg->externalMessageId);
        $this->assertSame(MessageKind::Image, $imgMsg->kind);
        $this->assertNull($imgMsg->body);
        $this->assertCount(1, $imgMsg->attachments);
        $this->assertSame(MessageDirection::Outbound, $imgMsg->direction);

        /** @var MediaRefDTO $att */
        $att = $imgMsg->attachments[0];
        $this->assertInstanceOf(MediaRefDTO::class, $att);
        $this->assertSame(MessageKind::Image, $att->kind);
        $this->assertSame('https://img.lazada.vn/photo.jpg', $att->externalUrl);
        $this->assertSame(800, $att->width);
        $this->assertSame(600, $att->height);
        $this->assertSame('image/jpeg', $att->mime);

        // has_more = false → no nextCursor
        $this->assertFalse($page->hasMore);
        $this->assertNull($page->nextCursor);

        // Assert GET to correct endpoint with mandatory partner_id in signed params.
        Http::assertSent(function ($r) {
            return str_contains($r->url(), '/im/message/list')
                && str_contains($r->url(), 'session_id=SESS_X')
                && str_contains($r->url(), 'sign=')
                && str_contains($r->url(), 'partner_id=');
        });
    }

    /**
     * Regression: incremental sync phải gửi start_time = HIỆN TẠI (cận trên, lùi dần) — KHÔNG dùng
     * `since` làm start_time. Truyền mốc cũ sẽ chỉ trả session cũ hơn ⇒ bỏ sót tin mới (deploy "không
     * đồng bộ"). `since` chỉ để LỌC + DỪNG khi đã lùi quá mốc sync.
     */
    public function test_lazada_fetch_conversations_uses_now_start_time_and_stops_at_since(): void
    {
        config(['integrations.lazada' => [
            'app_key' => 'K', 'app_secret' => 'S', 'api_base_url' => 'https://api.lazada.vn/rest',
        ]]);

        $sinceMs = 1716100000000;                       // mốc last sync
        $since = CarbonImmutable::createFromTimestampMs($sinceMs);

        Http::fake(['api.lazada.vn/*' => Http::response(['code' => '0', 'data' => [
            'session_list' => [
                ['session_id' => 'SESS_NEW', 'buyer_id' => '1', 'title' => 'Mới', 'last_message_time' => '1716200000000'],  // > since
                ['session_id' => 'SESS_OLD', 'buyer_id' => '2', 'title' => 'Cũ', 'last_message_time' => '1716000000000'],   // < since
            ],
            'has_more' => 'true', 'next_start_time' => '1716000000000', 'last_session_id' => 'SESS_OLD',
        ]], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'lazada_chat', externalShopId: 'S1', accessToken: 'TOK');
        $page = (new LazadaChatConnector)->fetchConversations($auth, ['since' => $since]);

        // start_time gửi đi là ~hiện tại (> mọi mốc trong fake), KHÔNG phải $sinceMs.
        Http::assertSent(function ($r) use ($sinceMs) {
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

            return str_contains($r->url(), '/im/session/list')
                && isset($q['start_time'])
                && (int) $q['start_time'] > 1716200000000   // > session mới nhất ⇒ là "now", không phải since
                && (int) $q['start_time'] !== $sinceMs;
        });

        // Chỉ session MỚI (>= since) được giữ; session cũ bị lọc ⇒ dừng phân trang.
        $this->assertCount(1, $page->items);
        $this->assertSame('SESS_NEW', $page->items[0]->externalConversationId);
        $this->assertFalse($page->hasMore, 'gặp session cũ hơn since ⇒ dừng');
        $this->assertNull($page->nextCursor);
    }

    /** Regression song song cho messages: start_time = now, lọc + dừng theo `since`. */
    public function test_lazada_fetch_messages_uses_now_start_time_and_stops_at_since(): void
    {
        config(['integrations.lazada' => [
            'app_key' => 'K', 'app_secret' => 'S', 'api_base_url' => 'https://api.lazada.vn/rest',
        ]]);

        $sinceMs = 1716100000000;
        $since = CarbonImmutable::createFromTimestampMs($sinceMs);

        Http::fake(['api.lazada.vn/*' => Http::response(['code' => '0', 'data' => [
            'message_list' => [
                ['message_id' => 'M_NEW', 'template_id' => '1', 'from_account_type' => '1', 'content' => json_encode(['txt' => 'mới']), 'send_time' => '1716200000000'],
                ['message_id' => 'M_OLD', 'template_id' => '1', 'from_account_type' => '1', 'content' => json_encode(['txt' => 'cũ']), 'send_time' => '1716000000000'],
            ],
            'has_more' => 'true', 'next_start_time' => '1716000000000', 'last_message_id' => 'M_OLD',
        ]], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'lazada_chat', externalShopId: 'S1', accessToken: 'TOK');
        $page = (new LazadaChatConnector)->fetchMessages($auth, 'SESS_X', ['since' => $since]);

        Http::assertSent(function ($r) use ($sinceMs) {
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

            return str_contains($r->url(), '/im/message/list')
                && isset($q['start_time'])
                && (int) $q['start_time'] > 1716200000000
                && (int) $q['start_time'] !== $sinceMs;
        });

        $this->assertCount(1, $page->items);
        $this->assertSame('M_NEW', $page->items[0]->externalMessageId);
        $this->assertFalse($page->hasMore);
        $this->assertNull($page->nextCursor);
    }
}
