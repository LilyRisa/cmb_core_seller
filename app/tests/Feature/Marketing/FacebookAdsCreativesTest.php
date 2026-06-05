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

    public function test_fetch_ad_creatives_resolves_link_for_link_video_and_advantage(): void
    {
        Http::fake(['graph.facebook.com/*/ads*' => Http::response(['data' => [
            // Website link ad — URL on link_data.link.
            ['id' => 'AD_LINK', 'creative' => ['object_story_spec' => ['link_data' => [
                'link' => 'https://shop.example/sp', 'message' => 'Mua ngay',
            ]]]],
            // Video ad — URL only on the video CTA value.
            ['id' => 'AD_VIDEO', 'creative' => ['object_story_spec' => ['video_data' => [
                'title' => 'Video tiêu đề',
                'call_to_action' => ['type' => 'SHOP_NOW', 'value' => ['link' => 'https://shop.example/video']],
            ]]]],
            // Advantage+/dynamic creative — URL on asset_feed_spec.link_urls.
            ['id' => 'AD_ADV', 'creative' => ['asset_feed_spec' => [
                'link_urls' => [['website_url' => 'https://shop.example/adv']],
                'bodies' => [['text' => 'Nội dung động']], 'titles' => [['text' => 'Tiêu đề động']],
                'call_to_action_types' => ['LEARN_MORE'],
            ]]],
        ]], 200)]);

        $list = $this->connector()->fetchAdCreatives('tok', 'act_1');

        $this->assertSame('https://shop.example/sp', $list[0]->linkUrl);
        $this->assertSame('https://shop.example/video', $list[1]->linkUrl);
        $this->assertSame('Video tiêu đề', $list[1]->headline);
        $this->assertSame('SHOP_NOW', $list[1]->cta);
        $this->assertSame('https://shop.example/adv', $list[2]->linkUrl);
        $this->assertSame('Nội dung động', $list[2]->primaryText);
        $this->assertSame('Tiêu đề động', $list[2]->headline);
        $this->assertSame('LEARN_MORE', $list[2]->cta);
    }

    public function test_fetch_ad_creatives_throws_on_error(): void
    {
        Http::fake(['graph.facebook.com/*/ads*' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fetchAdCreatives failed');
        $this->connector()->fetchAdCreatives('tok', 'act_1');
    }
}
