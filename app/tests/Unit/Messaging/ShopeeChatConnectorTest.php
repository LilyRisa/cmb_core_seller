<?php

namespace Tests\Unit\Messaging;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeClient;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeWebhookVerifier;
use CMBcoreSeller\Integrations\Messaging\DTO\ConversationDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
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

    public function test_fetch_conversations_returns_empty_on_permission_error(): void
    {
        // App type chưa được cấp quyền Seller Chat → Shopee trả error_api_permission.
        Http::fake(['*get_conversation_list*' => Http::response([
            'error' => 'error_api_permission',
            'message' => 'This app type has no permission to this API.',
        ], 403)]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'shopee', externalShopId: '123', accessToken: 'tok',
        );

        $page = $this->connector()->fetchConversations($auth);

        $this->assertSame([], $page->items);
        $this->assertFalse($page->hasMore);
        $this->assertNull($page->nextCursor);
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

        $this->assertSame(MessageKind::Text, $event->kind);
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

        $this->assertSame(MessageKind::Image, $event->kind);
        $this->assertNull($event->body);
        $this->assertCount(1, $event->attachments);

        $att = $event->attachments[0];
        $this->assertInstanceOf(MediaRefDTO::class, $att);
        $this->assertSame(MessageKind::Image, $att->kind);
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

        $this->assertSame(MessageKind::Video, $event->kind);
        $this->assertNull($event->body);
        $this->assertCount(1, $event->attachments);

        $att = $event->attachments[0];
        $this->assertSame(MessageKind::Video, $att->kind);
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

        $this->assertSame(MessageKind::Text, $event->kind);
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
        $this->app->forgetInstance(MessagingRegistry::class);

        $registry = $this->app->make(MessagingRegistry::class);
        $this->assertTrue($registry->has('shopee_chat'));
        $this->assertInstanceOf(ShopeeChatConnector::class, $registry->for('shopee_chat'));
    }

    // --- Webhook-only: polling TẮT (tránh gọi sellerchat/get_* fail) ----------

    public function test_inbound_polling_disabled_webhook_still_true(): void
    {
        $c = $this->connector();
        // Polling tắt: `sellerchat/get_*` là endpoint cộng đồng chưa verify ⇒ poll fail nhiều.
        // Shopee nhận chat realtime qua webhook (push code 10) nên không cần poll.
        $this->assertFalse($c->supports('inbound.polling'), 'inbound.polling phải false — Shopee chat webhook-only');
        $this->assertTrue($c->supports('inbound.webhook'), 'webhook vẫn là đường realtime chính');
    }

    public function test_fetch_conversations_parses_list_and_pagination(): void
    {
        Http::fake(['*/api/v2/sellerchat/get_conversation_list*' => Http::response(['error' => '', 'response' => [
            'conversations' => [
                ['conversation_id' => 'CONV_A', 'to_id' => 8740891, 'to_name' => 'Buyer A', 'to_avatar' => 'https://cf.shopee.vn/a.jpg',
                    'unread_count' => 2, 'last_message_timestamp' => 1726044721000000, 'latest_message_content' => ['text' => 'Còn hàng không?']],
                ['conversation_id' => 'CONV_B', 'to_id' => 8740892, 'to_name' => 'Buyer B', 'to_avatar' => '',
                    'unread_count' => 0, 'last_message_timestamp' => 1726040000000000, 'latest_message_content' => ['text' => 'ok']],
            ],
            'page_result' => ['next_cursor' => '1726040000000000', 'has_next_page' => true],
        ]], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $page = $this->connector()->fetchConversations($auth);

        $this->assertCount(2, $page->items);
        /** @var ConversationDTO $first */
        $first = $page->items[0];
        $this->assertSame('CONV_A', $first->externalConversationId);
        $this->assertSame('8740891', $first->buyerExternalId);
        $this->assertSame('Buyer A', $first->buyerName);
        $this->assertSame('https://cf.shopee.vn/a.jpg', $first->buyerAvatarUrl);
        $this->assertSame('Còn hàng không?', $first->lastMessagePreview);
        $this->assertSame(2, $first->unreadCount);
        $this->assertNotNull($first->lastMessageAt);
        $this->assertNull($page->items[1]->buyerAvatarUrl);   // empty to_avatar → null

        $this->assertTrue($page->hasMore);
        $this->assertSame('1726040000000000', $page->nextCursor);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/sellerchat/get_conversation_list')
            && str_contains($r->url(), 'sign=')
            && str_contains($r->url(), 'access_token=ACCESS_1'));
    }

    public function test_fetch_messages_parses_direction_kind_and_attachment(): void
    {
        Http::fake(['*/api/v2/sellerchat/get_message*' => Http::response(['error' => '', 'response' => [
            'messages' => [
                // Buyer → seller (from_shop_id=0) ⇒ Inbound, text.
                ['message_id' => 'M_TXT', 'message_type' => 'text', 'from_id' => 8740891, 'to_id' => 55,
                    'from_shop_id' => 0, 'content' => ['text' => 'Bao giờ ship?'], 'created_timestamp' => 1726044721],
                // Seller → buyer (from_shop_id=55 = shop) ⇒ Outbound, image.
                ['message_id' => 'M_IMG', 'message_type' => 'image', 'from_id' => 55, 'to_id' => 8740891,
                    'from_shop_id' => 55, 'content' => ['url' => 'https://cf.shopee.vn/img.jpg', 'thumb_width' => 400, 'thumb_height' => 711],
                    'created_timestamp' => 1726044725],
            ],
            'page_result' => ['next_offset' => '', 'has_next_page' => false],
        ]], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $page = $this->connector()->fetchMessages($auth, 'CONV_A');

        $this->assertCount(2, $page->items);
        /** @var MessageDTO $txt */
        $txt = $page->items[0];
        $this->assertSame('M_TXT', $txt->externalMessageId);
        $this->assertSame('CONV_A', $txt->externalConversationId);
        $this->assertSame(MessageKind::Text, $txt->kind);
        $this->assertSame('Bao giờ ship?', $txt->body);
        $this->assertSame(MessageDirection::Inbound, $txt->direction);
        $this->assertSame('8740891', $txt->buyerExternalId);
        $this->assertNotNull($txt->sentAt);

        /** @var MessageDTO $img */
        $img = $page->items[1];
        $this->assertSame(MessageKind::Image, $img->kind);
        $this->assertNull($img->body);
        $this->assertSame(MessageDirection::Outbound, $img->direction);
        $this->assertSame('8740891', $img->buyerExternalId);   // outbound ⇒ buyer = to_id
        $this->assertCount(1, $img->attachments);
        /** @var MediaRefDTO $att */
        $att = $img->attachments[0];
        $this->assertSame('https://cf.shopee.vn/img.jpg', $att->externalUrl);
        $this->assertSame(400, $att->width);
        $this->assertSame(711, $att->height);

        $this->assertFalse($page->hasMore);
        $this->assertNull($page->nextCursor);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/sellerchat/get_message')
            && str_contains($r->url(), 'conversation_id=CONV_A')
            && str_contains($r->url(), 'sign='));
    }

    public function test_fetch_conversations_filters_and_stops_at_since(): void
    {
        // Incremental: hội thoại cũ hơn `since` bị lọc bỏ ⇒ dừng phân trang (không quét lại lịch sử).
        Http::fake(['*/api/v2/sellerchat/get_conversation_list*' => Http::response(['error' => '', 'response' => [
            'conversations' => [
                ['conversation_id' => 'CONV_NEW', 'to_id' => 1, 'last_message_timestamp' => 1726044721000000],  // > since
                ['conversation_id' => 'CONV_OLD', 'to_id' => 2, 'last_message_timestamp' => 1726000000000000],  // < since
            ],
            'page_result' => ['next_cursor' => '1726000000000000', 'has_next_page' => true],
        ]], 200)]);

        $since = CarbonImmutable::createFromTimestamp(1726020000);
        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $page = $this->connector()->fetchConversations($auth, ['since' => $since]);

        $this->assertCount(1, $page->items);
        $this->assertSame('CONV_NEW', $page->items[0]->externalConversationId);
        $this->assertFalse($page->hasMore, 'gặp hội thoại cũ hơn since ⇒ dừng');
        $this->assertNull($page->nextCursor);
    }
}
