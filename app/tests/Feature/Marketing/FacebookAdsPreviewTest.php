<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsPreviewTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_generate_previews_returns_iframe_per_format(): void
    {
        Http::fake(['graph.facebook.com/*/generatepreviews*' => Http::response([
            'data' => [['body' => '<iframe src="x"></iframe>']],
        ], 200)]);

        $previews = $this->connector()->generatePreviews(
            'TOK', 'act_1',
            ['page_id' => '123', 'link_data' => ['message' => 'Hi']],
            ['DESKTOP_FEED_STANDARD', 'MOBILE_FEED_STANDARD'],
        );

        $this->assertCount(2, $previews);
        $this->assertSame('DESKTOP_FEED_STANDARD', $previews[0]->format);
        $this->assertStringContainsString('<iframe', $previews[0]->body);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'act_1/generatepreviews') && $r->data()['ad_format'] === 'DESKTOP_FEED_STANDARD');
    }

    public function test_generate_previews_uses_object_story_id_for_existing_post(): void
    {
        Http::fake(['graph.facebook.com/*/generatepreviews*' => Http::response([
            'data' => [['body' => '<iframe src="x"></iframe>']],
        ], 200)]);

        $this->connector()->generatePreviews('TOK', 'act_1', ['object_story_id' => '123_456'], ['MOBILE_FEED_STANDARD']);

        Http::assertSent(function ($r) {
            $creative = json_decode(urldecode((string) ($r->data()['creative'] ?? '')), true);

            return ($creative['object_story_id'] ?? null) === '123_456'
                && ! array_key_exists('object_story_spec', $creative);
        });
    }

    public function test_generate_previews_skips_formats_that_error(): void
    {
        Http::fake(['graph.facebook.com/*/generatepreviews*' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $previews = $this->connector()->generatePreviews('TOK', 'act_1', ['page_id' => '123'], ['DESKTOP_FEED_STANDARD']);

        $this->assertSame([], $previews);
    }
}
