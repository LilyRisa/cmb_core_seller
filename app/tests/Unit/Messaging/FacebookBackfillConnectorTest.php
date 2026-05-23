<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookSignatureVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookBackfillConnectorTest extends TestCase
{
    private function connector(): FacebookPageConnector
    {
        return new FacebookPageConnector(
            ['app_secret' => 'x', 'graph_version' => 'v19.0', 'app_id' => 'app123'],
            new FacebookSignatureVerifier,
        );
    }

    private function auth(): MessagingAuthContext
    {
        return new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );
    }

    public function test_supports_backfill_capability(): void
    {
        $this->assertTrue($this->connector()->supports('inbound.backfill'));
    }

    public function test_fetch_page_profile_returns_name_and_avatar(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'name' => 'My Shop Page',
                'picture' => ['data' => ['url' => 'https://cdn.fb/pageavatar.jpg']],
                'id' => 'PAGE_123',
            ], 200),
        ]);

        $profile = $this->connector()->fetchPageProfile($this->auth());

        $this->assertSame('My Shop Page', $profile['name']);
        $this->assertSame('https://cdn.fb/pageavatar.jpg', $profile['avatar_url']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/PAGE_123')
            && (str_contains($r->url(), 'fields=name%2Cpicture') || str_contains($r->url(), 'fields=name,picture')));
    }

    public function test_fetch_user_profile_returns_name_and_avatar(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'name' => 'Nguyen Van A',
                'profile_pic' => 'https://cdn.fb/psidavatar.jpg',
                'id' => 'PSID_999',
            ], 200),
        ]);

        $profile = $this->connector()->fetchUserProfile($this->auth(), 'PSID_999');

        $this->assertSame('Nguyen Van A', $profile['name']);
        $this->assertSame('https://cdn.fb/psidavatar.jpg', $profile['avatar_url']);
    }

    public function test_fetch_user_profile_falls_back_to_first_last_name(): void
    {
        // FB User Profile API thường chỉ trả first_name/last_name (không có `name`).
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'first_name' => 'Văn A',
                'last_name' => 'Nguyễn',
                'profile_pic' => 'https://cdn.fb/psidavatar.jpg',
                'id' => 'PSID_555',
            ], 200),
        ]);

        $profile = $this->connector()->fetchUserProfile($this->auth(), 'PSID_555');

        $this->assertSame('Văn A Nguyễn', $profile['name']);
        $this->assertSame('https://cdn.fb/psidavatar.jpg', $profile['avatar_url']);
    }

    public function test_fetch_profile_failure_returns_nulls(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);
        $profile = $this->connector()->fetchUserProfile($this->auth(), 'PSID_X');
        $this->assertNull($profile['name']);
        $this->assertNull($profile['avatar_url']);
    }

    public function test_fetch_conversations_maps_thread_and_psid(): void
    {
        Http::fake([
            'graph.facebook.com/*conversations*' => Http::response([
                'data' => [[
                    'id' => 't_aaa',
                    'updated_time' => '2026-05-20T10:00:00+0000',
                    'message_count' => 12,
                    'snippet' => 'tin gần nhất',
                    'participants' => ['data' => [
                        ['id' => 'PAGE_123', 'name' => 'My Page'],
                        ['id' => 'PSID_999', 'name' => 'Nguyen Van A'],
                    ]],
                ]],
                'paging' => ['cursors' => ['after' => 'CURSOR_2'], 'next' => 'https://graph.facebook.com/next'],
            ], 200),
        ]);

        $page = $this->connector()->fetchConversations($this->auth(), ['pageSize' => 25]);

        $this->assertCount(1, $page->items);
        $dto = $page->items[0];
        $this->assertSame('PSID_999', $dto->externalConversationId);
        $this->assertSame('PSID_999', $dto->buyerExternalId);
        $this->assertSame('Nguyen Van A', $dto->buyerName);
        $this->assertSame('t_aaa', $dto->raw['fb_thread_id']);
        $this->assertSame(12, $dto->raw['message_count']);
        $this->assertSame('CURSOR_2', $page->nextCursor);
        $this->assertTrue($page->hasMore);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/PAGE_123/conversations')
            && str_contains($r->url(), 'platform=MESSENGER'));
    }

    public function test_fetch_conversations_paginates_with_after_cursor(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [], 'paging' => []], 200)]);

        $this->connector()->fetchConversations($this->auth(), ['cursor' => 'CURSOR_2', 'pageSize' => 25]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'after=CURSOR_2'));
    }

    public function test_fetch_messages_maps_direction_and_attachments(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_aaa',
                'messages' => ['data' => [
                    [
                        'id' => 'm_out', 'message' => 'Chào anh', 'created_time' => '2026-05-20T10:01:00+0000',
                        'from' => ['id' => 'PAGE_123', 'name' => 'My Page'],
                    ],
                    [
                        'id' => 'm_in', 'message' => '', 'created_time' => '2026-05-20T10:00:00+0000',
                        'from' => ['id' => 'PSID_999', 'name' => 'A'],
                        'attachments' => ['data' => [[
                            'mime_type' => 'image/jpeg', 'name' => 'photo.jpg',
                            'image_data' => ['url' => 'https://cdn.fb/photo.jpg'],
                        ]]],
                    ],
                ]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_aaa', 'pageSize' => 50]);

        $this->assertCount(2, $page->items);

        $out = $page->items[0];
        $this->assertSame('m_out', $out->externalMessageId);
        $this->assertSame('PSID_999', $out->externalConversationId);   // PSID, không phải thread id
        $this->assertSame('outbound', $out->direction->value);
        $this->assertSame('text', $out->kind->value);

        $in = $page->items[1];
        $this->assertSame('inbound', $in->direction->value);
        $this->assertSame('image', $in->kind->value);
        $this->assertCount(1, $in->attachments);
        $this->assertSame('https://cdn.fb/photo.jpg', $in->attachments[0]->externalUrl);
        $this->assertSame('image/jpeg', $in->attachments[0]->mime);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/t_aaa'));
    }

    public function test_fetch_messages_sticker_creates_image_attachment(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_sticker',
                'messages' => ['data' => [[
                    'id' => 'm_sticker',
                    'message' => '',
                    'created_time' => '2026-05-20T11:00:00+0000',
                    'from' => ['id' => 'PSID_999', 'name' => 'A'],
                    'sticker' => 'https://external.xx.fbcdn.net/sticker/369239263222822.png',
                ]]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_sticker', 'pageSize' => 50]);

        $this->assertCount(1, $page->items);
        $msg = $page->items[0];
        $this->assertSame('inbound', $msg->direction->value);
        $this->assertSame('image', $msg->kind->value);
        $this->assertCount(1, $msg->attachments);
        $att = $msg->attachments[0];
        $this->assertSame('https://external.xx.fbcdn.net/sticker/369239263222822.png', $att->externalUrl);
        $this->assertSame('image/png', $att->mime);
        $this->assertSame('sticker', $att->filename);
        $this->assertNull($msg->body);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'sticker'));
    }

    public function test_fetch_messages_sticker_with_fallback_does_not_set_body(): void
    {
        // Sticker kèm fallback có URL trùng ⇒ giữ ảnh sticker, KHÔNG đẩy URL vào body.
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_st2',
                'messages' => ['data' => [[
                    'id' => 'm_st2',
                    'message' => '',
                    'created_time' => '2026-05-20T11:00:00+0000',
                    'from' => ['id' => 'PSID_999', 'name' => 'A'],
                    'sticker' => 'https://external.xx.fbcdn.net/sticker/abc.png',
                    'attachments' => ['data' => [[
                        'type' => 'fallback',
                        'url' => 'https://external.xx.fbcdn.net/sticker/abc.png',
                    ]]],
                ]]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_st2', 'pageSize' => 50]);

        $msg = $page->items[0];
        $this->assertSame('image', $msg->kind->value);
        $this->assertNull($msg->body, 'sticker không được set body thành link fallback');
    }

    public function test_fetch_messages_recovers_generic_template_via_per_message_fetch(): void
    {
        // Messages edge trả tin RỖNG (không có generic_template). fetchMessages phải gọi
        // /{mid}?fields=message,attachments để phục hồi title + nút bấm (tin tự động page).
        Http::fake([
            // per-message endpoint (URL chứa mid) → generic_template đầy đủ.
            'graph.facebook.com/*m_tpl*' => Http::response([
                'id' => 'm_tpl',
                'message' => '',
                'attachments' => ['data' => [[
                    'generic_template' => [
                        'title' => 'Ưu đãi hôm nay: 220.000đ',
                        'cta' => [
                            ['title' => 'Đặt hàng ngay', 'type' => 'postback'],
                            ['title' => 'Xem web', 'type' => 'web_url', 'url' => 'https://shop.vn/sp'],
                        ],
                    ],
                ]]],
            ], 200),
            // messages edge → tin rỗng, KHÔNG attachments.
            'graph.facebook.com/*' => Http::response([
                'id' => 't_tpl',
                'messages' => ['data' => [[
                    'id' => 'm_tpl',
                    'message' => '',
                    'created_time' => '2026-05-22T09:44:39+0000',
                    'from' => ['id' => 'PAGE_123', 'name' => 'Shop'],
                ]]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_tpl', 'pageSize' => 50]);

        $msg = $page->items[0];
        $this->assertSame('outbound', $msg->direction->value);
        $this->assertSame('text', $msg->kind->value);
        $this->assertSame('Ưu đãi hôm nay: 220.000đ', $msg->body);
        $this->assertCount(2, $msg->meta['buttons']);
        $this->assertSame('Đặt hàng ngay', $msg->meta['buttons'][0]['title']);
        $this->assertSame('https://shop.vn/sp', $msg->meta['buttons'][1]['url']);

        // Đã gọi endpoint per-message để phục hồi nội dung.
        Http::assertSent(fn ($r) => str_contains($r->url(), '/m_tpl') && str_contains(urldecode($r->url()), 'fields=message,attachments'));
        // Query edge KHÔNG nhúng generic_template (tránh Graph 400 làm vỡ backfill).
        Http::assertSent(fn ($r) => str_contains($r->url(), '/t_tpl') && ! str_contains(urldecode($r->url()), 'generic_template'));
    }

    public function test_fetch_messages_shared_link_sets_body(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_share',
                'messages' => ['data' => [[
                    'id' => 'm_share',
                    'message' => '',
                    'created_time' => '2026-05-20T12:00:00+0000',
                    'from' => ['id' => 'PSID_999', 'name' => 'A'],
                    'attachments' => ['data' => [[
                        'type' => 'share',
                        'title' => 'Sản phẩm hay',
                        'url' => 'https://www.facebook.com/share/p/abc123',
                    ]]],
                ]]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_share', 'pageSize' => 50]);

        $this->assertCount(1, $page->items);
        $msg = $page->items[0];
        $this->assertSame('text', $msg->kind->value);
        $this->assertCount(0, $msg->attachments);
        $this->assertSame('Sản phẩm hay https://www.facebook.com/share/p/abc123', $msg->body);
    }

    public function test_fetch_messages_shared_link_without_title_uses_url_only(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_share2',
                'messages' => ['data' => [[
                    'id' => 'm_share2',
                    'message' => '',
                    'created_time' => '2026-05-20T12:01:00+0000',
                    'from' => ['id' => 'PSID_999', 'name' => 'A'],
                    'attachments' => ['data' => [[
                        'type' => 'fallback',
                        'url' => 'https://example.com/article',
                    ]]],
                ]]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_share2', 'pageSize' => 50]);

        $this->assertCount(1, $page->items);
        $msg = $page->items[0];
        $this->assertSame('text', $msg->kind->value);
        $this->assertSame('https://example.com/article', $msg->body);
    }

    public function test_fetch_messages_graph_fields_include_sticker_and_attachment_extras(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 't_x', 'messages' => ['data' => []]], 200),
        ]);

        $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_x', 'pageSize' => 20]);

        Http::assertSent(function ($r) {
            $fields = urldecode($r->url());
            return str_contains($fields, 'sticker')
                && str_contains($fields, 'type')
                && str_contains($fields, 'title');
        });
    }

    public function test_fetch_messages_shares_edge_sets_body_when_text_empty(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_shares',
                'messages' => ['data' => [[
                    'id' => 'm_shares',
                    'message' => '',
                    'created_time' => '2026-05-20T13:00:00+0000',
                    'from' => ['id' => 'PSID_999', 'name' => 'A'],
                    'shares' => ['data' => [[
                        'name' => 'Bài viết hay',
                        'link' => 'https://www.facebook.com/post/xyz',
                    ]]],
                ]]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_shares', 'pageSize' => 50]);

        $this->assertCount(1, $page->items);
        $msg = $page->items[0];
        $this->assertSame('text', $msg->kind->value);
        $this->assertSame('Bài viết hay https://www.facebook.com/post/xyz', $msg->body);
    }

    public function test_fetch_messages_graph_fields_include_shares(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 't_x', 'messages' => ['data' => []]], 200)]);

        $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_x', 'pageSize' => 20]);

        Http::assertSent(fn ($r) => str_contains(urldecode($r->url()), 'shares'));
    }

    public function test_outbound_window_allows_human_agent_tag(): void
    {
        $policy = $this->connector()->outboundWindow();
        $this->assertContains('HUMAN_AGENT', $policy->allowedTags);
        $this->assertSame(24, $policy->freeWindowHours);
        $this->assertTrue($policy->requiresTag);
    }
}
