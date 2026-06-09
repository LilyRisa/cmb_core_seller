<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\AdMonitor;
use CMBcoreSeller\Modules\Marketing\Services\AdMonitorEvaluator;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Giám sát tự-động chạy cho TikTok (write tối thiểu): pause khi vượt ngưỡng. Đồng thời
 * chứng minh map "Kết quả" đa-provider (TikTok actions rỗng ⇒ dùng results connector).
 */
class TikTokAdMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['tiktok'], 'integrations.ads_tiktok' => ['app_id' => 'A', 'app_secret' => 'S']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    public function test_monitor_pauses_tiktok_campaign_over_threshold(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $account = AdAccount::create(['provider' => 'tiktok', 'external_account_id' => '123', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'AT']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'c1', 'name' => 'CD', 'objective' => 'TRAFFIC', 'status' => 'ACTIVE', 'daily_budget' => 100000]);
        AdMonitor::create(['tenant_id' => $tenant->id, 'ad_account_id' => $account->id, 'target_level' => 'campaign', 'target_external_id' => 'c1', 'enabled' => true, 'pause_enabled' => true, 'pause_above' => 50000, 'min_results' => 1]);

        Http::fake([
            // report/integrated/get: campaign-level có chi tiêu vượt ngưỡng (cpr=100k>50k), adgroup-level rỗng.
            'business-api.tiktok.com/*report/integrated/get*' => function ($request) {
                $isCampaign = str_contains($request->url(), 'AUCTION_CAMPAIGN');

                return Http::response(['code' => 0, 'data' => [
                    'list' => $isCampaign ? [[
                        'dimensions' => ['campaign_id' => 'c1'],
                        'metrics' => ['spend' => '200000', 'impressions' => '1000', 'clicks' => '50', 'conversion' => '2'],
                    ]] : [],
                    'page_info' => ['total_page' => 1],
                ]], 200);
            },
            'business-api.tiktok.com/*campaign/status/update*' => Http::response(['code' => 0, 'data' => []], 200),
        ]);

        $actions = app(AdMonitorEvaluator::class)->evaluateAccount($account->refresh());

        $this->assertCount(1, $actions);
        $this->assertSame('pause', $actions[0]['type']);
        $this->assertSame('PAUSED', AdEntity::withoutGlobalScopes()->where('external_id', 'c1')->value('status'));
        $this->assertDatabaseHas('ad_monitor_actions', ['ad_account_id' => $account->id, 'target_external_id' => 'c1', 'type' => 'pause', 'results' => 2]);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/campaign/status/update/')
            && ($req['operation_status'] ?? null) === 'DISABLE' && ($req['advertiser_id'] ?? null) === '123');
    }
}
