<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsCreateTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_create_campaign_posts_mapped_objective_and_returns_id(): void
    {
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['id' => 'C_NEW'], 200)]);

        $id = $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(objective: 'messages', name: 'Camp'));

        $this->assertSame('C_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();

            return str_contains($request->url(), 'act_1/campaigns')
                && $d['objective'] === 'OUTCOME_ENGAGEMENT'
                && $d['status'] === 'PAUSED'
                && array_key_exists('special_ad_categories', $d);
        });
    }

    public function test_create_adset_scales_budget_and_sets_messaging_spec(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS_NEW'], 200)]);

        $id = $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C_NEW', objective: 'messages',
            dailyBudgetMajor: 150000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]],
            pageId: '123', startTime: null,
        ));

        $this->assertSame('AS_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();
            $promoted = json_decode($d['promoted_object'] ?? '{}', true);

            return str_contains($request->url(), 'act_1/adsets')
                && $d['campaign_id'] === 'C_NEW'
                && $d['daily_budget'] === '150000'
                && $d['optimization_goal'] === 'CONVERSATIONS'
                && $d['billing_event'] === 'IMPRESSIONS'
                && $d['destination_type'] === 'MESSENGER'
                && ($promoted['page_id'] ?? null) === '123';
        });
    }

    public function test_create_ad_from_existing_page_post_uses_object_story_id(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_NEW'], 200)]);

        $id = $this->connector()->createAd('tok', 'act_1', new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS_NEW', pageId: '123',
            pagePostId: '123_456', cta: 'MESSAGE_PAGE',
        ));

        $this->assertSame('AD_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();
            $creative = json_decode($d['creative'] ?? '{}', true);

            return str_contains($request->url(), 'act_1/ads')
                && $d['adset_id'] === 'AS_NEW'
                && ($creative['object_story_id'] ?? null) === '123_456';
        });
    }

    public function test_create_ad_new_creative_uses_object_story_spec(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_NEW2'], 200)]);

        $this->connector()->createAd('tok', 'act_1', new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS_NEW', pageId: '123',
            imageHash: 'HASH', primaryText: 'Mua ngay', headline: 'Sale', linkUrl: 'https://shop.vn', cta: 'SHOP_NOW',
        ));

        Http::assertSent(function ($request) {
            $creative = json_decode($request->data()['creative'] ?? '{}', true);
            $spec = $creative['object_story_spec'] ?? [];

            return ($spec['page_id'] ?? null) === '123'
                && ($spec['link_data']['image_hash'] ?? null) === 'HASH'
                && ($spec['link_data']['call_to_action']['type'] ?? null) === 'SHOP_NOW';
        });
    }
}
