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

class SyncAdInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    private function seedAccount(): AdAccount
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1', 'status' => 'active', 'access_token' => 'T',
        ]);
        AdEntity::create([
            'ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'Camp', 'status' => 'ACTIVE',
        ]);

        return $account;
    }

    public function test_upserts_snapshot_with_finalizing_and_idempotent(): void
    {
        $account = $this->seedAccount();
        Http::fake(['graph.facebook.com/*/insights*' => Http::response([
            'data' => [[
                'date_start' => now()->toDateString(), 'date_stop' => now()->toDateString(),
                'spend' => '50000', 'impressions' => '1000', 'clicks' => '30', 'reach' => '800',
                'ctr' => '3.0', 'cpc' => '1666', 'cpm' => '50000', 'frequency' => '1.25',
                'purchase_roas' => [['value' => '2.5']],
            ]],
        ], 200)]);

        $job = new SyncAdInsights($account->id);
        $job->handle(app(AdsRegistry::class));
        $job->handle(app(AdsRegistry::class)); // idempotent

        $rows = AdInsightSnapshot::withoutGlobalScopes()->where('external_id', 'C1')->get();
        $this->assertCount(1, $rows); // upsert, not duplicate
        $this->assertSame(50000, (int) $rows[0]->spend);
        $this->assertTrue((bool) $rows[0]->is_finalizing); // today is within 28d window
        $this->assertSame(2.5, (float) $rows[0]->purchase_roas);
        $this->assertNotNull($account->fresh()->insights_synced_at);
    }

    public function test_throttle_hot_sets_flag(): void
    {
        $account = $this->seedAccount();
        Http::fake(['graph.facebook.com/*/insights*' => Http::response(
            ['data' => []],
            200,
            ['x-fb-ads-insights-throttle' => '{"app_id_util_pct":95.0,"acc_id_util_pct":90.0,"ads_api_access_tier":"standard_access"}'],
        )]);

        (new SyncAdInsights($account->id))->handle(app(AdsRegistry::class));

        $this->assertTrue((bool) ($account->fresh()->meta['insights_throttled'] ?? false));
    }
}
