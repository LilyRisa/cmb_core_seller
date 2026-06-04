<?php

namespace Tests\Feature\Marketing;

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
}
