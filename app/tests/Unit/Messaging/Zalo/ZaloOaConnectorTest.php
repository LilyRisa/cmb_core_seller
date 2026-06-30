<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloClient;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloOaConnector;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloSignatureVerifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

class ZaloOaConnectorTest extends TestCase
{
    private function connector(): ZaloOaConnector
    {
        return new ZaloOaConnector(
            ['app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa_secret_xyz', 'redirect_uri' => 'https://x.test/oauth/zalo_oa/callback'],
            new ZaloSignatureVerifier,
            new ZaloClient,
        );
    }

    public function test_identity_and_interfaces(): void
    {
        $c = $this->connector();
        $this->assertSame('zalo_oa', $c->code());
        $this->assertInstanceOf(MessagingConnector::class, $c);
        $this->assertInstanceOf(InteractiveMessagingConnector::class, $c);
    }

    public function test_capability_map(): void
    {
        $c = $this->connector();
        $this->assertTrue($c->supports('inbound.webhook'));
        $this->assertTrue($c->supports('inbound.postback'));
        $this->assertTrue($c->supports('outbound.text'));
        $this->assertTrue($c->supports('outbound.image'));
        $this->assertTrue($c->supports('outbound.file'));
        $this->assertTrue($c->supports('outbound.interactive'));
        $this->assertTrue($c->supports('read_receipt'));
        $this->assertFalse($c->supports('outbound.video'));
        $this->assertFalse($c->supports('outbound.utility_template'));
        $this->assertFalse($c->supports('inbound.polling'));
        $this->assertFalse($c->supports('outbound.template'));
        $this->assertFalse($c->supports('typing'));
    }

    public function test_comment_ops_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->hideComment(
            new MessagingAuthContext(1, 'zalo_oa', 'oa1', 'TKN'),
            'c1', true,
        );
    }

    public function test_verify_webhook_signature_delegates_to_verifier(): void
    {
        $body = '{"app_id":"app_123","event_name":"user_send_text","timestamp":"1700000000"}';
        $mac = 'mac='.hash('sha256', 'app_123'.$body.'1700000000'.'oa_secret_xyz');
        $req = Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => $mac], $body);

        $this->assertTrue($this->connector()->verifyWebhookSignature($req));
    }

    public function test_verify_webhook_signature_rejects_bad(): void
    {
        $req = Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => 'mac=bad'], '{"timestamp":"1"}');
        $this->assertFalse($this->connector()->verifyWebhookSignature($req));
    }

    public function test_oa_secret_falls_back_to_app_secret_when_empty(): void
    {
        // oa_secret để trống ⇒ dùng app_secret để verify (vì thường trùng nhau).
        $connector = new ZaloOaConnector(
            ['app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => '', 'redirect_uri' => 'https://x.test/cb'],
            new ZaloSignatureVerifier,
            new ZaloClient,
        );
        $body = '{"app_id":"app_123","event_name":"user_send_text","timestamp":"1700000000"}';
        $mac = 'mac='.hash('sha256', 'app_123'.$body.'1700000000'.'sec');
        $req = Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => $mac], $body);

        $this->assertTrue($connector->verifyWebhookSignature($req));
    }

    private function webhookRequest(array $payload): Request
    {
        return Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], [], json_encode($payload));
    }

    public function test_parse_user_send_text(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_text', 'timestamp' => '1700000000',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_1', 'text' => 'Còn hàng không shop?'],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame('OA_9', $dto->externalShopId);
        $this->assertSame('USER_1', $dto->buyerExternalId);
        $this->assertSame('USER_1', $dto->externalConversationId);
        $this->assertSame('MID_1', $dto->externalMessageId);
        $this->assertSame('Còn hàng không shop?', $dto->body);
        $this->assertSame(MessageKind::Text, $dto->kind);
    }

    public function test_parse_user_send_image_builds_attachment(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_image', 'timestamp' => '1700000001',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_2', 'attachments' => [['type' => 'image', 'payload' => ['url' => 'https://zalo.test/a.jpg']]]],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame(MessageKind::Image, $dto->kind);
        $this->assertCount(1, $dto->attachments);
        $this->assertSame('https://zalo.test/a.jpg', $dto->attachments[0]->externalUrl);
    }

    public function test_parse_user_send_video_builds_attachment(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_video', 'timestamp' => '1700000010',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_V', 'attachments' => [['type' => 'video', 'payload' => ['url' => 'https://zalo.test/v.mp4']]]],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame(MessageKind::Video, $dto->kind);
        $this->assertCount(1, $dto->attachments);
        $this->assertSame('https://zalo.test/v.mp4', $dto->attachments[0]->externalUrl);
    }

    public function test_parse_user_send_video_without_attachment_falls_back_to_body(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_video', 'timestamp' => '1700000011',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_V2'],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame(MessageKind::Text, $dto->kind);
        $this->assertSame('[Video]', $dto->body);
        $this->assertCount(0, $dto->attachments);
    }

    public function test_parse_user_send_gif_builds_image_attachment(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_gif', 'timestamp' => '1700000012',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_G', 'attachments' => [['type' => 'gif', 'payload' => ['url' => 'https://zalo.test/g.gif']]]],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame(MessageKind::Image, $dto->kind);
        $this->assertCount(1, $dto->attachments);
        $this->assertSame('https://zalo.test/g.gif', $dto->attachments[0]->externalUrl);
    }

    public function test_parse_user_send_gif_without_attachment_falls_back_to_body(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_gif', 'timestamp' => '1700000013',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_G2'],
        ]));

        $this->assertSame(MessageKind::Text, $dto->kind);
        $this->assertSame('[Ảnh động]', $dto->body);
    }

    public function test_parse_user_send_sticker_builds_image_attachment(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_sticker', 'timestamp' => '1700000014',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_S', 'attachments' => [['type' => 'sticker', 'payload' => ['url' => 'https://zalo.test/s.png']]]],
        ]));

        $this->assertSame(MessageKind::Image, $dto->kind);
        $this->assertCount(1, $dto->attachments);
        $this->assertSame('https://zalo.test/s.png', $dto->attachments[0]->externalUrl);
    }

    public function test_parse_user_send_sticker_without_url_falls_back_to_body(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_sticker', 'timestamp' => '1700000015',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_S2'],
        ]));

        $this->assertSame(MessageKind::Text, $dto->kind);
        $this->assertSame('[Nhãn dán]', $dto->body);
        $this->assertCount(0, $dto->attachments);
    }

    public function test_parse_user_send_location_falls_back_to_vietnamese_body(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_location', 'timestamp' => '1700000016',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_L', 'attachments' => [['type' => 'location', 'payload' => ['coordinates' => ['latitude' => '10.77', 'longitude' => '106.69']]]]],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame(MessageKind::Text, $dto->kind);
        $this->assertStringContainsString('[Vị trí]', (string) $dto->body);
        $this->assertNotSame('[location]', $dto->body);
    }

    public function test_parse_user_send_business_card_body(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_business_card', 'timestamp' => '1700000017',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_BC'],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame(MessageKind::Text, $dto->kind);
        $this->assertSame('[Danh thiếp]', $dto->body);
    }

    public function test_parse_user_send_sticker_uses_vietnamese_not_raw_english(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_sticker', 'timestamp' => '1700000018',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_S3'],
        ]));
        $this->assertNotSame('[sticker]', $dto->body);
    }

    public function test_parse_postback(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_text', 'timestamp' => '1700000002',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_3', 'text' => 'postback_eyJub2RlX2lkIjoibjEifQ=='],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_POSTBACK, $dto->type);
        $this->assertSame('postback_eyJub2RlX2lkIjoibjEifQ==', $dto->body);
    }

    public function test_parse_seen_and_unknown(): void
    {
        $seen = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_seen_message', 'timestamp' => '1700000003',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
        ]));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_READ, $seen->type);

        $unknown = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'oa_send_text', 'timestamp' => '1700000004',
            'sender' => ['id' => 'OA_9'], 'recipient' => ['id' => 'USER_1'],
        ]));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_UNKNOWN, $unknown->type);
    }

    public function test_build_authorization_url(): void
    {
        $url = $this->connector()->buildAuthorizationUrl('STATE_X');
        $this->assertStringContainsString('oauth.zaloapp.com/v4/oa/permission', $url);
        $this->assertStringContainsString('app_id=app_123', $url);
        $this->assertStringContainsString('state=STATE_X', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringNotContainsString('scope=', $url);
    }

    public function test_build_authorization_url_with_pkce_challenge(): void
    {
        $url = $this->connector()->buildAuthorizationUrl('STATE_X', ['code_challenge' => 'CH_ABC']);
        $this->assertStringContainsString('code_challenge=CH_ABC', $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);
    }

    public function test_pkce_challenge_is_base64url_sha256_no_padding(): void
    {
        $verifier = 'the_verifier_123';
        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $this->assertSame($expected, ZaloOaConnector::pkceChallenge($verifier));
        // base64url: không có +, /, =
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', ZaloOaConnector::pkceChallenge($verifier));
    }

    public function test_exchange_code_pkce_sends_code_verifier(): void
    {
        Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'], 200)]);

        $this->connector()->exchangeCodeForTokenPkce('CODE_1', 'VERIFIER_XYZ');

        Http::assertSent(fn ($r) => $r['grant_type'] === 'authorization_code' && $r['code'] === 'CODE_1' && ($r['code_verifier'] ?? null) === 'VERIFIER_XYZ');
    }

    public function test_exchange_code_for_token(): void
    {
        Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'], 200)]);

        $token = $this->connector()->exchangeCodeForToken('CODE_1');

        $this->assertSame('AT', $token->accessToken);
        $this->assertSame('RT', $token->refreshToken);
        $this->assertNotNull($token->expiresAt);
        Http::assertSent(fn ($r) => $r['grant_type'] === 'authorization_code' && $r['code'] === 'CODE_1' && $r->hasHeader('secret_key', 'sec'));
    }

    public function test_refresh_token_rotates(): void
    {
        Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT2', 'refresh_token' => 'RT2', 'expires_in' => '90000'], 200)]);

        $token = $this->connector()->refreshToken('RT1');

        $this->assertSame('AT2', $token->accessToken);
        $this->assertSame('RT2', $token->refreshToken);
        Http::assertSent(fn ($r) => $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === 'RT1');
    }

    public function test_fetch_user_profile(): void
    {
        Http::fake(['openapi.zalo.me/v3.0/oa/user/detail*' => Http::response(['error' => 0, 'data' => ['user_id' => 'USER_1', 'display_name' => 'Nguyễn A', 'avatar' => 'https://zalo.test/av.jpg']], 200)]);

        $profile = $this->connector()->fetchUserProfile(new MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN'), 'USER_1');

        $this->assertSame('Nguyễn A', $profile['name']);
        $this->assertSame('https://zalo.test/av.jpg', $profile['avatar_url']);
    }

    public function test_fetch_oa_id(): void
    {
        Http::fake(['openapi.zalo.me/v2.0/oa/getoa' => Http::response(['error' => 0, 'data' => ['oa_id' => 'OA_9', 'name' => 'Shop Zalo']], 200)]);

        $oaId = $this->connector()->fetchOaId(new MessagingAuthContext(0, 'zalo_oa', '', 'TKN'));

        $this->assertSame('OA_9', $oaId);
    }

    public function test_send_text_posts_cs_shape(): void
    {
        Http::fake(['openapi.zalo.me/v3.0/oa/message/cs' => Http::response(['error' => 0, 'message' => 'Success', 'data' => ['message_id' => 'OUT_1', 'user_id' => 'USER_1']], 200)]);

        $auth = new MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN');
        $result = $this->connector()->sendText($auth, 'USER_1', 'Dạ còn hàng ạ!');

        $this->assertSame('OUT_1', $result->externalMessageId);
        Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), '/v3.0/oa/message/cs')
                && $r->hasHeader('access_token', 'TKN')
                && ($d['recipient']['user_id'] ?? null) === 'USER_1'
                && ($d['message']['text'] ?? null) === 'Dạ còn hàng ạ!';
        });
    }

    public function test_send_media_image_uploads_then_sends(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('media/x.jpg', 'BYTES');

        Http::fake([
            'openapi.zalo.me/v2.0/oa/upload/image' => Http::response(['error' => 0, 'data' => ['attachment_id' => 'ATT_1']], 200),
            'openapi.zalo.me/v3.0/oa/message/cs' => Http::response(['error' => 0, 'data' => ['message_id' => 'OUT_2']], 200),
        ]);

        $media = new MediaRefDTO(
            kind: MessageKind::Image,
            mime: 'image/jpeg', storagePath: 'media/x.jpg', filename: 'x.jpg',
        );
        $auth = new MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN');

        $result = $this->connector()->sendMedia($auth, 'USER_1', $media, ['disk' => 'local']);

        $this->assertSame('OUT_2', $result->externalMessageId);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v2.0/oa/upload/image'));
        Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), '/v3.0/oa/message/cs')
                && (($d['message']['attachment']['payload']['template_type'] ?? null) === 'media');
        });
    }

    public function test_send_interactive_maps_buttons_and_caps_at_5(): void
    {
        Http::fake(['openapi.zalo.me/v3.0/oa/message/cs' => Http::response(['error' => 0, 'data' => ['message_id' => 'OUT_3']], 200)]);

        $auth = new MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN');
        $structure = [
            'text' => 'Chọn nhé',
            'buttons' => [
                ['title' => 'Website', 'url' => 'https://shop.test'],
                ['title' => 'Đặt hàng', 'payload' => 'ENC_1'],
                ['title' => 'B3', 'payload' => 'p3'], ['title' => 'B4', 'payload' => 'p4'],
                ['title' => 'B5', 'payload' => 'p5'], ['title' => 'B6_DROP', 'payload' => 'p6'],
            ],
        ];

        $result = $this->connector()->sendInteractive($auth, 'USER_1', $structure);
        $this->assertSame('OUT_3', $result->externalMessageId);

        Http::assertSent(function ($r) {
            $btns = $r->data()['message']['attachment']['payload']['buttons'] ?? [];

            return count($btns) === 5
                && $btns[0]['type'] === 'oa.open.url'
                && $btns[1]['type'] === 'oa.query.hide'
                && $btns[1]['payload'] === 'postback_ENC_1';
        });
    }
}
