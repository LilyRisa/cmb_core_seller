<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdInsights;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SyncAdInsights nhánh `insights.account_report` (TikTok): gộp 1 report call/level
 * cho cả account và map row về đúng AdEntity theo external_id.
 */
class TikTokSyncInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.ads' => ['tiktok'],
            'integrations.ads_tiktok' => ['app_id' => 'A', 'app_secret' => 'S'],
        ]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    public function test_account_scoped_sync_maps_report_rows_to_entities(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create([
            'provider' => 'tiktok', 'external_account_id' => '123', 'status' => 'active', 'access_token' => 'AT',
        ]);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'c1', 'name' => 'Camp', 'status' => 'ENABLE']);

        Http::fake([
            // account-level (AUCTION_ADVERTISER) + campaign-level (AUCTION_CAMPAIGN) đều qua report endpoint.
            'business-api.tiktok.com/*report/integrated/get*' => function ($request) {
                $isCampaign = str_contains($request->url(), 'AUCTION_CAMPAIGN');
                $dim = $isCampaign ? ['campaign_id' => 'c1'] : ['advertiser_id' => '123'];

                return Http::response([
                    'code' => 0, 'data' => [
                        'list' => [['dimensions' => $dim, 'metrics' => ['spend' => '70000', 'impressions' => '500', 'clicks' => '20', 'reach' => '400', 'conversion' => '3']]],
                        'page_info' => ['total_page' => 1],
                    ],
                ], 200);
            },
        ]);

        (new SyncAdInsights($account->id))->handle(app(AdsRegistry::class));

        // Account-level snapshot.
        $acctSnap = AdInsightSnapshot::withoutGlobalScopes()->where('level', 'account')->where('external_id', '123')->first();
        $this->assertNotNull($acctSnap);
        $this->assertSame(70000, (int) $acctSnap->spend);

        // Campaign snapshot gắn đúng ad_entity_id qua nhánh account-scoped.
        $entityId = (int) AdEntity::withoutGlobalScopes()->where('external_id', 'c1')->value('id');
        $campSnap = AdInsightSnapshot::withoutGlobalScopes()->where('level', 'campaign')->where('external_id', 'c1')->first();
        $this->assertNotNull($campSnap);
        $this->assertSame($entityId, (int) $campSnap->ad_entity_id);
        $this->assertSame(70000, (int) $campSnap->spend);
        $this->assertNotNull($account->fresh()->insights_synced_at);
    }
}
