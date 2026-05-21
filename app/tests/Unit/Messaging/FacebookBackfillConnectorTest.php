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
}
