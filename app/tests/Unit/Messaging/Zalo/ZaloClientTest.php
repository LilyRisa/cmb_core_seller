<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloApiException;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZaloClientTest extends TestCase
{
    public function test_post_returns_data_and_sends_access_token_header(): void
    {
        Http::fake(['openapi.zalo.me/v3.0/oa/message/cs' => Http::response(['error' => 0, 'message' => 'Success', 'data' => ['message_id' => 'm1']], 200)]);

        $data = (new ZaloClient)->post('TKN', 'v3.0/oa/message/cs', ['recipient' => ['user_id' => 'u1']]);

        $this->assertSame('m1', $data['message_id']);
        Http::assertSent(fn ($r) => $r->hasHeader('access_token', 'TKN') && str_contains($r->url(), 'openapi.zalo.me/v3.0/oa/message/cs'));
    }

    public function test_throws_on_nonzero_error_even_with_http_200(): void
    {
        Http::fake(['openapi.zalo.me/*' => Http::response(['error' => -216, 'message' => 'User has blocked OA'], 200)]);

        $this->expectException(ZaloApiException::class);
        try {
            (new ZaloClient)->post('TKN', 'v3.0/oa/message/cs', []);
        } catch (ZaloApiException $e) {
            $this->assertSame(-216, $e->zaloError);
            throw $e;
        }
    }

    public function test_oauth_token_posts_form_with_secret_key_header(): void
    {
        Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'], 200)]);

        $res = (new ZaloClient)->oauthToken(['code' => 'C', 'app_id' => 'A', 'grant_type' => 'authorization_code'], 'SECRET');

        $this->assertSame('AT', $res['access_token']);
        Http::assertSent(fn ($r) => $r->hasHeader('secret_key', 'SECRET')
            && $r['grant_type'] === 'authorization_code'
            && str_contains((string) $r->header('Content-Type')[0], 'application/x-www-form-urlencoded'));
    }
}
