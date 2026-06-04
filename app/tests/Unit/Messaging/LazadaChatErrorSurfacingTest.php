<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Lazada\LazadaChatConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lazada IM read endpoints must SURFACE API errors (code != 0 / non-2xx) instead
 * of swallowing them and returning an empty page — otherwise an app-permission /
 * token / sign error looks identical to "0 conversations" and the poll job reports
 * success with sync_error empty. Mirrors send()'s existing error handling.
 */
class LazadaChatErrorSurfacingTest extends TestCase
{
    private function auth(): MessagingAuthContext
    {
        return new MessagingAuthContext(
            channelAccountId: 1,
            provider: 'lazada_chat',
            externalShopId: 'SELLER_1',
            accessToken: 'TOK',
        );
    }

    public function test_fetch_conversations_throws_on_lazada_error_envelope(): void
    {
        config(['integrations.messaging_lazada_im.app_key' => 'LK', 'integrations.messaging_lazada_im.app_secret' => 'SEC']);
        Http::fake(['*/im/session/list*' => Http::response([
            'type' => 'ISV',
            'code' => 'InsufficientPermission',
            'message' => 'App does not have permission to access this api',
        ], 200)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('InsufficientPermission');

        (new LazadaChatConnector)->fetchConversations($this->auth());
    }

    public function test_fetch_messages_throws_on_lazada_error_envelope(): void
    {
        config(['integrations.messaging_lazada_im.app_key' => 'LK', 'integrations.messaging_lazada_im.app_secret' => 'SEC']);
        Http::fake(['*/im/message/list*' => Http::response([
            'type' => 'ISV',
            'code' => 'IllegalAccessToken',
            'message' => 'token invalid',
        ], 200)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IllegalAccessToken');

        (new LazadaChatConnector)->fetchMessages($this->auth(), 'SESS_1');
    }

    public function test_fetch_conversations_succeeds_on_ok_envelope(): void
    {
        config(['integrations.messaging_lazada_im.app_key' => 'LK', 'integrations.messaging_lazada_im.app_secret' => 'SEC']);
        Http::fake(['*/im/session/list*' => Http::response([
            'code' => '0',
            'data' => ['session_list' => [], 'has_more' => false],
        ], 200)]);

        $page = (new LazadaChatConnector)->fetchConversations($this->auth());
        $this->assertSame([], $page->items);
    }
}
