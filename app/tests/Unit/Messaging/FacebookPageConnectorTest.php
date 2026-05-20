<?php

namespace Tests\Unit\Messaging;

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

    public function test_ignores_echo_messages(): void
    {
        $payload = json_encode([
            'object' => 'page',
            'entry' => [['id' => 'P', 'messaging' => [[
                'sender' => ['id' => 'P'],
                'message' => ['mid' => 'm1', 'text' => 'echo', 'is_echo' => true],
            ]]]],
        ]);

        $event = $this->connector()->parseWebhook($this->request($payload, null));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_UNKNOWN, $event->type);
    }

    public function test_outbound_window_is_24h_with_tags(): void
    {
        $policy = $this->connector()->outboundWindow();
        $this->assertSame(24, $policy->freeWindowHours);
        $this->assertTrue($policy->requiresTag);
        $this->assertContains('POST_PURCHASE_UPDATE', $policy->allowedTags);
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
}
