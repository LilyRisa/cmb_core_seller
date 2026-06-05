<?php

namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * fetchInsights must parse the `actions` array into messaging conversations
 * (click-to-Messenger) and leads (lead ads) — the conversion metrics we reconcile
 * against manual orders. No webhook needed; comes straight from Insights.
 */
class FacebookAdsActionsTest extends TestCase
{
    public function test_fetch_insights_extracts_conversations_and_leads(): void
    {
        Http::fake(['graph.facebook.com/*C1/insights*' => Http::response([
            'data' => [[
                'date_start' => '2026-06-04', 'date_stop' => '2026-06-04', 'spend' => '60000',
                'impressions' => '2000', 'clicks' => '40', 'reach' => '1500',
                'actions' => [
                    ['action_type' => 'onsite_conversion.messaging_conversation_started_7d', 'value' => '12'],
                    ['action_type' => 'lead', 'value' => '5'],
                    ['action_type' => 'link_click', 'value' => '40'],
                    ['action_type' => 'omni_purchase', 'value' => '8'],
                    ['action_type' => 'offsite_conversion.fb_pixel_purchase', 'value' => '8'],
                ],
            ]],
        ], 200)]);

        $rows = (new FacebookAdsConnector(['graph_version' => 'v19.0']))->fetchInsights('AT', 'C1', 'campaign');

        $this->assertSame(12, $rows[0]->messagingConversations);
        $this->assertSame(5, $rows[0]->leads);
        $this->assertSame(8, $rows[0]->purchases); // omni_purchase preferred (not double-counted)
    }

    public function test_missing_actions_default_to_zero(): void
    {
        Http::fake(['graph.facebook.com/*C1/insights*' => Http::response([
            'data' => [['date_start' => '2026-06-04', 'date_stop' => '2026-06-04', 'spend' => '1000']],
        ], 200)]);

        $rows = (new FacebookAdsConnector(['graph_version' => 'v19.0']))->fetchInsights('AT', 'C1', 'campaign');
        $this->assertSame(0, $rows[0]->messagingConversations);
        $this->assertSame(0, $rows[0]->leads);
    }
}
