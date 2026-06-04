<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsCreativesTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_fetch_ad_creatives_maps_text_and_post_id(): void
    {
        Http::fake(['graph.facebook.com/*/ads*' => Http::response(['data' => [[
            'id' => 'AD1', 'name' => 'QC Tết', 'effective_status' => 'ACTIVE',
            'creative' => [
                'effective_object_story_id' => '123_456',
                'object_story_spec' => ['link_data' => [
                    'message' => 'Sale Tết giảm 30%', 'name' => 'Áo khoác hot',
                    'call_to_action' => ['type' => 'MESSAGE_PAGE'],
                ]],
            ],
        ]]], 200)]);

        $list = $this->connector()->fetchAdCreatives('tok', 'act_1');

        $this->assertCount(1, $list);
        $c = $list[0];
        $this->assertSame('AD1', $c->adId);
        $this->assertSame('QC Tết', $c->adName);
        $this->assertSame('Sale Tết giảm 30%', $c->primaryText);
        $this->assertSame('Áo khoác hot', $c->headline);
        $this->assertSame('MESSAGE_PAGE', $c->cta);
        $this->assertSame('123_456', $c->pagePostId);
        $this->assertTrue($this->connector()->supports('creatives.read'));
    }

    public function test_fetch_ad_creatives_throws_on_error(): void
    {
        Http::fake(['graph.facebook.com/*/ads*' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fetchAdCreatives failed');
        $this->connector()->fetchAdCreatives('tok', 'act_1');
    }
}
