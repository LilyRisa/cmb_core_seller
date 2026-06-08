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

            public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null, ?int $tenantId = null): array
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

    public function test_fetches_landing_page_for_website_creative(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $captured = new \stdClass;
        $captured->data = null;
        $this->app->instance(MarketingAnalysisClient::class, new class($captured) implements MarketingAnalysisClient
        {
            public function __construct(private \stdClass $h) {}

            public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null, ?int $tenantId = null): array
            {
                $this->h->data = $data;

                return ['payload' => ['summary' => 'ok'], 'provider_code' => 'fake', 'model' => 'fake-1'];
            }
        });

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD web', 'objective' => 'OUTCOME_SALES']);

        Http::fake([
            'graph.facebook.com/*/C1/insights*' => Http::sequence()
                ->push(['data' => [['campaign_id' => 'C1', 'spend' => '1000', 'clicks' => '5']]], 200)
                ->push(['data' => [['ad_id' => 'AD1', 'spend' => '1000', 'clicks' => '5']]], 200),
            'graph.facebook.com/*/ads*' => Http::response(['data' => [[
                'id' => 'AD1', 'name' => 'QC web', 'effective_status' => 'ACTIVE',
                'creative' => ['object_story_spec' => ['link_data' => ['message' => 'Mua ngay', 'link' => 'https://shop.example/sp']]],
            ]]], 200),
            'graph.facebook.com/v19.0/?ids=*' => Http::response([], 200),
            'shop.example/*' => Http::response('<html><head><title>Sản phẩm hot</title><meta name="description" content="Mua ngay"></head><body><h1>Khuyến mãi</h1><script>fbq("init","1")</script><form></form></body></html>', 200),
        ]);

        app(CampaignInsightAnalysisService::class)->generate($account, 'C1', [
            'days' => 7, 'metrics' => ['spend'], 'include_engagement' => false, 'include_landing' => true,
        ], true);

        $pages = $captured->data['landing_pages'];
        $this->assertCount(1, $pages);
        $this->assertSame('https://shop.example/sp', $pages[0]['url']);
        $this->assertSame('Sản phẩm hot', $pages[0]['title']);
        $this->assertContains('Khuyến mãi', $pages[0]['headings']);
        $this->assertContains('facebook_pixel', $pages[0]['pixels']);
        $this->assertTrue($pages[0]['has_form']);
    }

    public function test_resolves_landing_from_existing_post_cta(): void
    {
        // Most VN ads are built from an existing page post: the creative has NO link,
        // the destination lives in the post's call-to-action (read via a page token).
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $captured = new \stdClass;
        $captured->data = null;
        $this->app->instance(MarketingAnalysisClient::class, new class($captured) implements MarketingAnalysisClient
        {
            public function __construct(private \stdClass $h) {}

            public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null, ?int $tenantId = null): array
            {
                $this->h->data = $data;

                return ['payload' => ['summary' => 'ok'], 'provider_code' => 'fake', 'model' => 'fake-1'];
            }
        });

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD sale', 'objective' => 'OUTCOME_SALES']);

        Http::fake([
            'graph.facebook.com/*/C1/insights*' => Http::sequence()
                ->push(['data' => [['campaign_id' => 'C1', 'spend' => '1000', 'clicks' => '5']]], 200)
                ->push(['data' => [['ad_id' => 'AD1', 'spend' => '1000', 'clicks' => '5']]], 200),
            // Existing-post creative: effective_object_story_id set, NO link anywhere.
            'graph.facebook.com/*/ads*' => Http::response(['data' => [[
                'id' => 'AD1', 'name' => 'QC video', 'effective_status' => 'ACTIVE',
                'creative' => ['effective_object_story_id' => '727_111'],
            ]]], 200),
            'graph.facebook.com/*/me/accounts*' => Http::response(['data' => [
                ['id' => '727', 'name' => 'Shop', 'access_token' => 'PAGE_A'],
            ]], 200),
            // Batched post CTA read with the page token → the real landing URL.
            'graph.facebook.com/v19.0/?ids=*' => Http::response([
                '727_111' => ['id' => '727_111', 'call_to_action' => ['type' => 'ORDER_NOW', 'value' => ['link' => 'https://shop.example/d800']]],
            ], 200),
            'shop.example/*' => Http::response('<html><head><title>D800</title></head><body><h1>Loa D800</h1><form></form></body></html>', 200),
        ]);

        app(CampaignInsightAnalysisService::class)->generate($account, 'C1', [
            'days' => 7, 'metrics' => ['spend'], 'include_engagement' => false, 'include_landing' => true,
        ], true);

        $pages = $captured->data['landing_pages'];
        $this->assertCount(1, $pages);
        $this->assertSame('https://shop.example/d800', $pages[0]['url']);
        $this->assertSame('D800', $pages[0]['title']);
    }

    public function test_includes_ad_set_structure_and_budget_even_without_delivery(): void
    {
        // ABO campaign: NO campaign budget, budgets live on the 2 ad sets; each ad set
        // has 1 ad. Insights return nothing (chưa phân phối trong kỳ). The payload MUST
        // still carry the ad sets (with ABO budgets) + the ads from the synced tree, so
        // the model never claims "không có ad / không có ngân sách".
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $captured = new \stdClass;
        $captured->data = null;
        $this->app->instance(MarketingAnalysisClient::class, new class($captured) implements MarketingAnalysisClient
        {
            public function __construct(private \stdClass $h) {}

            public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null, ?int $tenantId = null): array
            {
                $this->h->data = $data;

                return ['payload' => ['summary' => 'ok'], 'provider_code' => 'fake', 'model' => 'fake-1'];
            }
        });

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD ABO', 'objective' => 'OUTCOME_SALES']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'adset', 'external_id' => 'AS1', 'parent_external_id' => 'C1', 'name' => 'Nhóm 1', 'status' => 'ACTIVE', 'daily_budget' => 50000, 'meta' => ['optimization_goal' => 'OFFSITE_CONVERSIONS']]);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'adset', 'external_id' => 'AS2', 'parent_external_id' => 'C1', 'name' => 'Nhóm 2', 'status' => 'ACTIVE', 'daily_budget' => 70000, 'meta' => ['optimization_goal' => 'LINK_CLICKS']]);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'ad', 'external_id' => 'AD1', 'parent_external_id' => 'AS1', 'name' => 'QC 1', 'status' => 'ACTIVE']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'ad', 'external_id' => 'AD2', 'parent_external_id' => 'AS2', 'name' => 'QC 2', 'status' => 'ACTIVE']);

        Http::fake([
            // Every insights level returns empty (no delivery in the window).
            'graph.facebook.com/*/C1/insights*' => Http::response(['data' => []], 200),
            'graph.facebook.com/*/ads*' => Http::response(['data' => []], 200),
            'graph.facebook.com/v19.0/?ids=*' => Http::response([], 200),
        ]);

        app(CampaignInsightAnalysisService::class)->generate($account, 'C1', [
            'days' => 7, 'metrics' => ['spend', 'clicks'], 'include_engagement' => false, 'include_landing' => false,
        ], true);

        $data = $captured->data;
        // The 2 ad sets are present with their ABO budgets, despite zero delivery.
        $this->assertCount(2, $data['ad_sets']);
        $byId = collect($data['ad_sets'])->keyBy('external_id');
        $this->assertSame('Nhóm 1', $byId['AS1']['name']);
        $this->assertSame(50000, $byId['AS1']['daily_budget']);
        $this->assertSame(70000, $byId['AS2']['daily_budget']);
        $this->assertSame('OFFSITE_CONVERSIONS', $byId['AS1']['optimization_goal']);
        // Both ads are listed from the synced tree even though insights returned nothing.
        $adIds = collect($data['ads'])->pluck('ad_id')->all();
        $this->assertContains('AD1', $adIds);
        $this->assertContains('AD2', $adIds);
        $this->assertSame('AS1', collect($data['ads'])->firstWhere('ad_id', 'AD1')['adset_id']);
    }

    public function test_without_ai_provider_produces_drawer_shape(): void
    {
        // No marketing_ai_providers row ⇒ real LlmMarketingAnalysisClient falls back to
        // the campaign stub, which MUST produce summary/recommendations the drawer renders.
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'Chiến dịch Tết', 'objective' => 'OUTCOME_SALES']);

        $insight = app(CampaignInsightAnalysisService::class)->generate($account, 'C1', [
            'days' => 14, 'metrics' => ['spend', 'clicks'], 'include_engagement' => false,
        ], true);

        $this->assertArrayHasKey('summary', $insight->payload);
        $this->assertStringContainsString('Chiến dịch Tết', $insight->payload['summary']);
        $this->assertNotEmpty($insight->payload['recommendations']);
        $this->assertSame('stub', $insight->payload['generated_by']);
        // Effectiveness score (0–100) the drawer renders as a gauge.
        $this->assertArrayHasKey('score', $insight->payload);
        $this->assertIsInt($insight->payload['score']);
        $this->assertGreaterThanOrEqual(0, $insight->payload['score']);
        $this->assertLessThanOrEqual(100, $insight->payload['score']);
    }
}
