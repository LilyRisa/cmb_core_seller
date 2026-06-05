<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsPostEngagementTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_empty_ids_makes_no_http_call(): void
    {
        Http::fake();

        $this->assertSame([], $this->connector()->fetchPostEngagement('TOK', []));

        Http::assertNothingSent();
    }

    public function test_maps_likes_comments_shares_message_keyed_by_post_id(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            '123_456' => [
                'id' => '123_456',
                'message' => 'Sale Tết',
                'likes' => ['summary' => ['total_count' => 1200]],
                'comments' => ['summary' => ['total_count' => 89]],
                'shares' => ['count' => 45],
            ],
            '123_789' => [
                'id' => '123_789',
            ],
        ], 200)]);

        $out = $this->connector()->fetchPostEngagement('TOK', ['123_456', '123_789']);

        $this->assertSame(1200, $out['123_456']['likes']);
        $this->assertSame(89, $out['123_456']['comments']);
        $this->assertSame(45, $out['123_456']['shares']);
        $this->assertSame('Sale Tết', $out['123_456']['message']);
        $this->assertSame(0, $out['123_789']['likes']);
        $this->assertNull($out['123_789']['message']);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'ids=123_456%2C123_789')
            || str_contains(urldecode($req->url()), 'ids=123_456,123_789'));
    }
}
