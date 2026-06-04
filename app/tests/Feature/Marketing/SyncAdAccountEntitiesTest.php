<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdAccountEntities;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncAdAccountEntitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    public function test_syncs_entity_tree_idempotent(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1', 'name' => 'Shop',
            'status' => 'active', 'access_token' => 'T',
        ]);

        Http::fake([
            'graph.facebook.com/*act_1/campaigns*' => Http::response(['data' => [['id' => 'C1', 'name' => 'Camp', 'status' => 'ACTIVE']]], 200),
            'graph.facebook.com/*act_1/adsets*' => Http::response(['data' => [['id' => 'AS1', 'name' => 'Set', 'status' => 'ACTIVE', 'campaign_id' => 'C1']]], 200),
            'graph.facebook.com/*act_1/ads*' => Http::response(['data' => [['id' => 'AD1', 'name' => 'Ad', 'status' => 'ACTIVE', 'adset_id' => 'AS1']]], 200),
        ]);

        $job = new SyncAdAccountEntities($account->id);
        $job->handle(app(AdsRegistry::class));
        $job->handle(app(AdsRegistry::class)); // re-run: must be idempotent

        $this->assertSame(1, AdEntity::withoutGlobalScopes()->where('level', 'campaign')->count());
        $this->assertSame(1, AdEntity::withoutGlobalScopes()->where('level', 'adset')->count());
        $this->assertSame(1, AdEntity::withoutGlobalScopes()->where('level', 'ad')->count());

        $campaign = AdEntity::withoutGlobalScopes()->where('external_id', 'C1')->firstOrFail();
        $adset = AdEntity::withoutGlobalScopes()->where('external_id', 'AS1')->firstOrFail();
        $ad = AdEntity::withoutGlobalScopes()->where('external_id', 'AD1')->firstOrFail();
        $this->assertSame((int) $campaign->id, (int) $adset->parent_id);
        $this->assertSame((int) $adset->id, (int) $ad->parent_id);
    }
}
