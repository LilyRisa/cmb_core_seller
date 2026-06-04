<?php

namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsInsightsTest extends TestCase
{
    public function test_fetch_insights_maps_metrics_and_throttle(): void
    {
        Http::fake(['graph.facebook.com/*C1/insights*' => Http::response([
            'data' => [[
                'date_start' => '2026-06-01', 'date_stop' => '2026-06-04',
                'spend' => '50000', 'impressions' => '1000', 'clicks' => '30',
                'reach' => '800', 'ctr' => '3.0', 'cpc' => '1666', 'cpm' => '50000',
                'frequency' => '1.25', 'purchase_roas' => [['value' => '2.5']],
            ]],
        ], 200, ['x-fb-ads-insights-throttle' => '{"app_id_util_pct":12.5,"acc_id_util_pct":4.0,"ads_api_access_tier":"standard_access"}'])]);

        $conn = new FacebookAdsConnector(['graph_version' => 'v19.0']);
        $throttle = null;
        $rows = $conn->fetchInsights('AT', 'C1', 'campaign', ['date_preset' => 'today'], $throttle);

        $this->assertSame(50000, $rows[0]->spend);
        $this->assertSame(2.5, $rows[0]->purchaseRoas);
        $this->assertInstanceOf(AdInsightThrottleDTO::class, $throttle);
        $this->assertSame('standard_access', $throttle->accessTier);
        $this->assertEqualsWithDelta(12.5, $throttle->appUtilPct, 0.01);
    }

    public function test_fetch_insights_throws_on_error(): void
    {
        Http::fake(['graph.facebook.com/*C1/insights*' => Http::response(['error' => ['message' => 'bad', 'code' => 100]], 400)]);
        $this->expectException(\RuntimeException::class);
        (new FacebookAdsConnector(['graph_version' => 'v19.0']))->fetchInsights('AT', 'C1', 'campaign');
    }
}
