<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Services\CampaignInsightAnalysisService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignInsightAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_data_filters_metrics_and_includes_engagement(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        // Capture the data sent to AI.
        $captured = new \stdClass;
        $captured->data = null;
        $this->app->instance(MarketingAnalysisClient::class, new class($captured) implements MarketingAnalysisClient
        {
            public function __construct(private \stdClass $h) {}

            public function analyze(array $data, string $instruction): array
            {
                $this->h->data = $data;

                return ['payload' => ['summary' => 'ok'], 'provider_code' => 'fake', 'model' => 'fake-1'];
            }
        });

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'Chiến dịch Tết', 'objective' => 'OUTCOME_SALES']);

        Http::fake([
            // campaign-level then ad-level both hit /insights
            'graph.facebook.com/*/C1/insights*' => Http::sequence()
                ->push(['data' => [['campaign_id' => 'C1', 'spend' => '1000', 'impressions' => '50', 'clicks' => '3', 'ctr' => '6.0', 'cpc' => '333']]], 200)
                ->push(['data' => [['ad_id' => 'AD1', 'spend' => '600', 'impressions' => '30', 'clicks' => '2', 'ctr' => '6.6', 'cpc' => '300']]], 200),
            'graph.facebook.com/*/ads*' => Http::response(['data' => [
                ['id' => 'AD1', 'name' => 'QC1', 'effective_status' => 'ACTIVE',
                    'creative' => ['effective_object_story_id' => '123_456', 'object_story_spec' => ['link_data' => ['message' => 'Mua ngay']]]],
                ['id' => 'AD_OTHER', 'name' => 'Khác', 'creative' => ['effective_object_story_id' => '123_999']],
            ]], 200),
            'graph.facebook.com/v19.0/?ids=*' => Http::response([
                '123_456' => ['id' => '123_456', 'message' => 'Mua ngay', 'likes' => ['summary' => ['total_count' => 100]], 'comments' => ['summary' => ['total_count' => 7]]],
            ], 200),
        ]);

        app(CampaignInsightAnalysisService::class)->generate($account, 'C1', [
            'days' => 7, 'metrics' => ['spend', 'clicks'], 'include_engagement' => true,
        ], true);

        $data = $captured->data;
        $this->assertSame(7, $data['days']);
        $this->assertSame('Chiến dịch Tết', $data['campaign']['name']);
        // Only the chosen metrics survive.
        $this->assertSame(['spend', 'clicks'], array_keys($data['campaign_metrics']));
        $this->assertSame(1000, $data['campaign_metrics']['spend']);
        // Only the campaign's own ad (AD1) is kept; AD_OTHER filtered out.
        $this->assertCount(1, $data['creatives']);
        $this->assertSame('AD1', $data['creatives'][0]['ad_id']);
        // Engagement keyed by post id.
        $this->assertSame(100, $data['engagement']['123_456']['likes']);
        $this->assertSame(7, $data['engagement']['123_456']['comments']);
    }
}
