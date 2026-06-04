<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
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
}
