<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contract test FacebookPageConnector (SPEC-0024 S2 §9.3).
 *
 * Xác minh được KHÔNG cần API thật: signature HMAC, parseWebhook, outboundWindow,
 * SHAPE request Send API (Http::fake), mapping lỗi 24h-window. Live call cần Page
 * token thật + app review (ngoài phạm vi unit test).
 */
class FacebookPageConnectorTest extends TestCase
{
    private const SECRET = 'app-secret-xyz';

    private function connector(): FacebookPageConnector
    {
        return new FacebookPageConnector(
            ['app_secret' => self::SECRET, 'graph_version' => 'v19.0', 'app_id' => 'app123'],
            new FacebookSignatureVerifier,
        );
    }

    private function request(string $body, ?string $signature): Request
    {
        $server = $signature !== null ? ['HTTP_X_HUB_SIGNATURE_256' => $signature] : [];

        return Request::create('/webhook/messaging/facebook', 'POST', [], [], [], $server, $body);
    }

    public function test_verifies_valid_signature(): void
    {
        $body = '{"object":"page"}';
        $sig = 'sha256='.hash_hmac('sha256', $body, self::SECRET);

        $this->assertTrue($this->connector()->verifyWebhookSignature($this->request($body, $sig)));
    }

    public function test_rejects_invalid_signature(): void
    {
        $body = '{"object":"page"}';
        $this->assertFalse($this->connector()->verifyWebhookSignature($this->request($body, 'sha256=deadbeef')));
        $this->assertFalse($this->connector()->verifyWebhookSignature($this->request($body, null)));
    }

    public function test_parses_inbound_message_webhook(): void
    {
        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'PAGE_123',
                'time' => 1716200000000,
                'messaging' => [[
                    'sender' => ['id' => 'PSID_999'],
                    'recipient' => ['id' => 'PAGE_123'],
                    'timestamp' => 1716200000000,
                    'message' => ['mid' => 'm_abc', 'text' => 'Shop ơi còn hàng không?'],
                ]],
            ]],
        ]);

        $event = $this->connector()->parseWebhook($this->request($payload, null));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('facebook_page', $event->provider);
        $this->assertSame('PAGE_123', $event->externalShopId);
        $this->assertSame('PSID_999', $event->externalConversationId);
        $this->assertSame('PSID_999', $event->buyerExternalId);
        $this->assertSame('m_abc', $event->externalMessageId);
    }

    public function test_parses_all_events_in_a_batch(): void
    {
        // Messenger gộp nhiều messaging event / POST — phải lấy HẾT (không mất tin).
        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'PAGE_123',
                'messaging' => [
                    ['sender' => ['id' => 'PSID_A'], 'message' => ['mid' => 'm_1', 'text' => 'tin 1']],
                    ['sender' => ['id' => 'PSID_B'], 'message' => ['mid' => 'm_2', 'text' => 'tin 2']],
                    ['sender' => ['id' => 'PSID_A'], 'read' => ['watermark' => 123]],
                ],
            ]],
        ]);

        $events = $this->connector()->parseWebhookEvents($this->request($payload, null));

        $this->assertCount(3, $events);
        $this->assertSame('m_1', $events[0]->externalMessageId);
        $this->assertSame('m_2', $events[1]->externalMessageId);
        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_READ, $events[2]->type);
        // parseWebhook (single) vẫn trả event đầu — backward compat.
        $this->assertSame('m_1', $this->connector()->parseWebhook($this->request($payload, null))->externalMessageId);
    }

    public function test_ignores_echo_from_own_app(): void
    {
        // Echo do CHÍNH app này gửi (app_id khớp config) ⇒ bỏ (đã ghi qua SendMessage).
        $payload = json_encode([
            'object' => 'page',
            'entry' => [['id' => 'PAGE_123', 'messaging' => [[
                'sender' => ['id' => 'PAGE_123'],
                'recipient' => ['id' => 'PSID_9'],
                'message' => ['mid' => 'm1', 'text' => 'echo', 'is_echo' => true, 'app_id' => 'app123'],
            ]]]],
        ]);

        $event = $this->connector()->parseWebhook($this->request($payload, null));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_UNKNOWN, $event->type);
    }

    public function test_captures_page_echo_button_template_as_outbound_with_buttons(): void
    {
        // Tin page tự gửi qua công cụ Facebook (app_id KHÁC) — gồm template có nút bấm.
        // Phải nhận thành OUTBOUND, body = text template, kèm nhãn nút bấm.
        $payload = json_encode([
            'object' => 'page',
            'entry' => [['id' => 'PAGE_123', 'messaging' => [[
                'sender' => ['id' => 'PAGE_123'],
                'recipient' => ['id' => 'PSID_9'],
                'timestamp' => 1716200000000,
                'message' => [
                    'mid' => 'm_echo_btn',
                    'is_echo' => true,
                    'app_id' => 999888,
                    'attachments' => [[
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'button',
                            'text' => 'Chào bạn! Bạn cần hỗ trợ gì?',
                            'buttons' => [
                                ['type' => 'postback', 'title' => 'Mua hàng', 'payload' => 'BUY'],
                                ['type' => 'web_url', 'title' => 'Xem sản phẩm', 'url' => 'https://shop.vn/sp'],
                            ],
                        ],
                    ]],
                ],
            ]]]],
        ]);

        $event = $this->connector()->parseWebhook($this->request($payload, null));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame(MessageDirection::Outbound, $event->direction);
        $this->assertSame('PSID_9', $event->externalConversationId, 'conversation = người nhận (buyer)');
        $this->assertSame('Chào bạn! Bạn cần hỗ trợ gì?', $event->body);
        $this->assertCount(2, $event->meta['buttons']);
        $this->assertSame('Mua hàng', $event->meta['buttons'][0]['title']);
        $this->assertSame('Xem sản phẩm', $event->meta['buttons'][1]['title']);
        $this->assertSame('https://shop.vn/sp', $event->meta['buttons'][1]['url']);
    }

    public function test_captures_quick_replies_on_inbound_message_as_buttons(): void
    {
        // Quick replies kèm tin → nhãn nút bấm (kể cả inbound).
        $payload = json_encode([
            'object' => 'page',
            'entry' => [['id' => 'PAGE_123', 'messaging' => [[
                'sender' => ['id' => 'PSID_9'],
                'recipient' => ['id' => 'PAGE_123'],
                'message' => [
                    'mid' => 'm_qr',
                    'text' => 'Chọn nhé',
                    'quick_replies' => [
                        ['content_type' => 'text', 'title' => 'Có', 'payload' => 'YES'],
                        ['content_type' => 'text', 'title' => 'Không', 'payload' => 'NO'],
                    ],
                ],
            ]]]],
        ]);

        $event = $this->connector()->parseWebhook($this->request($payload, null));

        $this->assertSame(MessageDirection::Inbound, $event->direction);
        $this->assertSame('Chọn nhé', $event->body);
        $this->assertCount(2, $event->meta['buttons']);
        $this->assertSame('Có', $event->meta['buttons'][0]['title']);
    }

    public function test_sticker_message_does_not_linkify_fallback_url(): void
    {
        // FB gửi sticker kèm cả attachment image (sticker_id) lẫn fallback có URL trùng.
        // Không được set body thành URL ⇒ tránh hiện cả sticker lẫn link text.
        $payload = json_encode([
            'object' => 'page',
            'entry' => [['id' => 'PAGE_123', 'messaging' => [[
                'sender' => ['id' => 'PSID_9'],
                'recipient' => ['id' => 'PAGE_123'],
                'timestamp' => 1716200000000,
                'message' => [
                    'mid' => 'm_st',
                    'sticker_id' => 369239263222822,
                    'attachments' => [
                        ['type' => 'image', 'payload' => ['sticker_id' => 369239263222822, 'url' => 'https://cdn.fb/sticker.png']],
                        ['type' => 'fallback', 'payload' => ['url' => 'https://cdn.fb/sticker.png']],
                    ],
                ],
            ]]]],
        ]);

        $event = $this->connector()->parseWebhook($this->request($payload, null));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('image', $event->kind->value);
        $this->assertCount(1, $event->attachments);
        $this->assertSame('https://cdn.fb/sticker.png', $event->attachments[0]->externalUrl);
        $this->assertNull($event->body, 'sticker không được set body thành link');
    }

    public function test_outbound_window_is_24h_with_tags(): void
    {
        $policy = $this->connector()->outboundWindow();
        $this->assertSame(24, $policy->freeWindowHours);
        $this->assertTrue($policy->requiresTag);
        $this->assertContains('POST_PURCHASE_UPDATE', $policy->allowedTags);
    }

    public function test_oauth_dialog_and_token_exchange_use_same_redirect_uri(): void
    {
        // Meta bắt buộc redirect_uri ở dialog login & lúc đổi code PHẢI giống hệt —
        // lệch ⇒ lỗi "redirect_uri mismatch". Connector dùng 1 URI canonical (APP_URL).
        config(['app.url' => 'https://app.cmbcore.com']);
        $expected = 'https://app.cmbcore.com/oauth/facebook_page/callback';

        $url = $this->connector()->buildAuthorizationUrl('state_1');
        $this->assertStringContainsString('redirect_uri='.urlencode($expected), $url);

        Http::fake(['graph.facebook.com/*' => Http::response(['access_token' => 'PAGE_USER_TOKEN'], 200)]);
        $token = $this->connector()->exchangeCodeForToken('CODE_123');
        $this->assertSame('PAGE_USER_TOKEN', $token->accessToken);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/oauth/access_token')
            && str_contains($r->url(), 'redirect_uri='.urlencode($expected)));
    }

    public function test_authorization_url_requests_business_management_scope(): void
    {
        // Cần `business_management` để liệt kê page thuộc Business Manager (tài khoản doanh nghiệp).
        $url = $this->connector()->buildAuthorizationUrl('state_1');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $scope = (string) ($q['scope'] ?? '');

        $this->assertStringContainsString('business_management', $scope);
        $this->assertStringContainsString('pages_show_list', $scope);
    }

    public function test_send_text_posts_correct_shape(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'PSID_999', 'message_id' => 'mid.OUT_1'], 200),
        ]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );

        $result = $this->connector()->sendText($auth, 'PSID_999', 'Còn hàng nhé anh/chị!');

        $this->assertSame('mid.OUT_1', $result->externalMessageId);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/me/messages')
                && ($data['recipient']['id'] ?? null) === 'PSID_999'
                && ($data['message']['text'] ?? null) === 'Còn hàng nhé anh/chị!'
                && ($data['messaging_type'] ?? null) === 'RESPONSE';
        });
    }

    public function test_register_webhooks_subscribes_feed_field(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['success' => true], 200),
        ]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1,
            provider: 'facebook_page',
            externalShopId: 'PAGE_123',
            accessToken: 'PAGE_TOKEN',
        );

        $this->connector()->registerWebhooks($auth);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = (string) ($data['subscribed_fields'] ?? '');

            return str_contains($request->url(), '/subscribed_apps')
                && str_contains($fields, 'feed')
                && str_contains($fields, 'messages')
                && str_contains($fields, 'message_echoes');
        });
    }

    public function test_send_maps_window_error_to_exception(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'outside window', 'code' => 10, 'error_subcode' => 2018278],
            ], 400),
        ]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );

        $this->expectException(OutboundWindowClosed::class);
        $this->connector()->sendText($auth, 'PSID_999', 'late message');
    }

    public function test_capabilities_include_interactive_and_postback(): void
    {
        $c = $this->connector();
        $this->assertTrue($c->supports('outbound.interactive'));
        $this->assertTrue($c->supports('inbound.postback'));
    }

    public function test_parses_postback_webhook(): void
    {
        // Buyer bấm nút (messaging_postbacks) — payload do builder sinh.
        $payload = json_encode([
            'object' => 'page',
            'entry' => [['id' => 'PAGE_123', 'messaging' => [[
                'sender' => ['id' => 'PSID_9'],
                'recipient' => ['id' => 'PAGE_123'],
                'timestamp' => 1716200000000,
                'postback' => ['mid' => 'm_pb_1', 'title' => 'Mua hàng', 'payload' => '{"t":"flow","n":"ask","h":"b_buy"}'],
            ]]]],
        ]);

        $event = $this->connector()->parseWebhook($this->request($payload, null));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_POSTBACK, $event->type);
        $this->assertSame('PSID_9', $event->externalConversationId, 'conversation = PSID người bấm');
        $this->assertSame('m_pb_1', $event->externalMessageId);
        $this->assertSame('{"t":"flow","n":"ask","h":"b_buy"}', $event->meta['postback_payload']);
        $this->assertSame('Mua hàng', $event->meta['postback_title']);
    }

    public function test_send_interactive_posts_button_template_shape(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => 'PSID_9', 'message_id' => 'mid.BTN_1'], 200),
        ]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );

        $result = $this->connector()->sendInteractive($auth, 'PSID_9', [
            'text' => 'Bạn cần gì ạ?',
            'buttons' => [
                ['type' => 'postback', 'title' => 'Mua hàng', 'payload' => 'PB_BUY'],
                ['type' => 'url', 'title' => 'Xem web', 'url' => 'https://shop.vn'],
                ['type' => 'postback', 'title' => 'Phí ship', 'payload' => 'PB_SHIP'],
                ['type' => 'postback', 'title' => 'Nút thừa (bị cắt)', 'payload' => 'PB_X'],
            ],
        ]);

        $this->assertSame('mid.BTN_1', $result->externalMessageId);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $payload = $data['message']['attachment']['payload'] ?? [];
            $buttons = $payload['buttons'] ?? [];

            return str_contains($request->url(), '/me/messages')
                && ($data['message']['attachment']['type'] ?? null) === 'template'
                && ($payload['template_type'] ?? null) === 'button'
                && ($payload['text'] ?? null) === 'Bạn cần gì ạ?'
                && count($buttons) === 3                                  // cắt còn 3 (giới hạn FB)
                && ($buttons[0]['type'] ?? null) === 'postback'
                && ($buttons[0]['payload'] ?? null) === 'PB_BUY'
                && ($buttons[1]['type'] ?? null) === 'web_url'
                && ($buttons[1]['url'] ?? null) === 'https://shop.vn';
        });
    }

    public function test_send_interactive_without_buttons_falls_back_to_text(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['message_id' => 'mid.TXT_1'], 200),
        ]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );

        $this->connector()->sendInteractive($auth, 'PSID_9', ['text' => 'Chỉ có text', 'buttons' => []]);

        Http::assertSent(fn ($request) => ($request->data()['message']['text'] ?? null) === 'Chỉ có text');
    }
}
