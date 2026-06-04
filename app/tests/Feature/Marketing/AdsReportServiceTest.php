<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Services\AdsReportService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    private function seedData(): AdAccount
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $acc = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'T']);
        AdEntity::create(['ad_account_id' => $acc->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'Camp 1', 'status' => 'ACTIVE', 'objective' => 'OUTCOME_SALES', 'daily_budget' => 100000]);
        AdEntity::create(['ad_account_id' => $acc->id, 'level' => 'campaign', 'external_id' => 'C2', 'name' => 'Camp 2', 'status' => 'PAUSED', 'objective' => 'OUTCOME_LEADS']);
        AdEntity::create(['ad_account_id' => $acc->id, 'level' => 'adset', 'external_id' => 'AS1', 'parent_external_id' => 'C1', 'name' => 'Set 1', 'status' => 'ACTIVE']);
        AdEntity::create(['ad_account_id' => $acc->id, 'level' => 'adset', 'external_id' => 'AS2', 'parent_external_id' => 'C2', 'name' => 'Set 2', 'status' => 'ACTIVE']);

        return $acc;
    }

    private function fakeInsights(): void
    {
        Http::fake(function ($request) {
            if (! str_contains($request->url(), '/insights')) {
                return Http::response([], 200);
            }
            $level = $request->data()['level'] ?? 'campaign';
            $rows = $level === 'adset'
                ? [['adset_id' => 'AS1', 'spend' => '10000', 'impressions' => '500', 'clicks' => '20', 'cpm' => '20000', 'cpc' => '500'],
                    ['adset_id' => 'AS2', 'spend' => '20000', 'impressions' => '900', 'clicks' => '30']]
                : [['campaign_id' => 'C1', 'spend' => '60000', 'impressions' => '2000', 'clicks' => '40', 'cpm' => '30000', 'cpc' => '1500'],
                    ['campaign_id' => 'C2', 'spend' => '5000', 'impressions' => '100', 'clicks' => '2']];

            return Http::response(['data' => $rows], 200);
        });
    }

    public function test_report_campaign_joins_metadata_and_insights(): void
    {
        $acc = $this->seedData();
        $this->fakeInsights();

        $rows = app(AdsReportService::class)->report($acc, 'campaign', '2026-06-01', '2026-06-04');
        $c1 = collect($rows)->firstWhere('external_id', 'C1');

        $this->assertCount(2, $rows);
        $this->assertSame('Camp 1', $c1['name']);
        $this->assertSame('OUTCOME_SALES', $c1['objective']);
        $this->assertSame(100000, $c1['daily_budget']);
        $this->assertSame(60000, $c1['insights']['spend']);
        $this->assertSame(30000, $c1['insights']['cpm']);
    }

    public function test_report_adset_drilldown_by_campaign(): void
    {
        $acc = $this->seedData();
        $this->fakeInsights();

        $rows = app(AdsReportService::class)->report($acc, 'adset', '2026-06-01', '2026-06-04', ['campaign_ids' => ['C1']]);

        $this->assertCount(1, $rows);                       // only AS1 (belongs to C1)
        $this->assertSame('AS1', $rows[0]['external_id']);
        $this->assertSame(10000, $rows[0]['insights']['spend']);
    }
}
