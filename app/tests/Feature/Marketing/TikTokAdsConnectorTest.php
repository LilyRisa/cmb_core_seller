<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Ads\TikTok\TikTokAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TikTokAdsConnector — read path qua Http::fake với response mẫu lấy từ tài liệu
 * tai_lieu_tiktok_ads/ (TikTok API for Business v1.3).
 */
class TikTokAdsConnectorTest extends TestCase
{
    private function connector(): TikTokAdsConnector
    {
        return new TikTokAdsConnector(['app_id' => 'A', 'app_secret' => 'S']);
    }

    public function test_exchange_code_returns_long_term_token_without_expiry(): void
    {
        Http::fake(['business-api.tiktok.com/*oauth2/access_token*' => Http::response([
            'code' => 0, 'message' => 'OK',
            'data' => ['access_token' => 'AT', 'advertiser_ids' => ['123'], 'scope' => [4]],
        ], 200)]);

        $token = $this->connector()->exchangeCodeForToken('AUTHCODE');

        $this->assertSame('AT', $token['access_token']);
        $this->assertNull($token['expires_at']); // token TikTok không hết hạn
        Http::assertSent(fn ($req) => str_contains($req->url(), '/oauth2/access_token/')
            && $req['auth_code'] === 'AUTHCODE' && $req['app_id'] === 'A');
    }

    public function test_exchange_code_throws_on_nonzero_code(): void
    {
        Http::fake(['business-api.tiktok.com/*' => Http::response(['code' => 40001, 'message' => 'bad auth_code'], 200)]);

        $this->expectException(\RuntimeException::class);
        $this->connector()->exchangeCodeForToken('BAD');
    }

    public function test_list_ad_accounts_merges_advertiser_get_and_info(): void
    {
        Http::fake([
            'business-api.tiktok.com/*oauth2/advertiser/get*' => Http::response([
                'code' => 0, 'data' => ['list' => [['advertiser_id' => '123', 'advertiser_name' => 'Shop']]],
            ], 200),
            'business-api.tiktok.com/*advertiser/info*' => Http::response([
                'code' => 0, 'data' => ['list' => [[
                    'advertiser_id' => '123', 'name' => 'Shop VN', 'currency' => 'VND',
                    'status' => 'STATUS_ENABLE', 'timezone' => 'Asia/Ho_Chi_Minh', 'owner_bc_id' => '999',
                ]]],
            ], 200),
        ]);

        $accounts = $this->connector()->listAdAccounts('AT');

        $this->assertCount(1, $accounts);
        $this->assertSame('123', $accounts[0]->externalAccountId);
        $this->assertSame('Shop VN', $accounts[0]->name);
        $this->assertSame('VND', $accounts[0]->currency);
        $this->assertSame('STATUS_ENABLE', $accounts[0]->status);
        $this->assertSame('999', $accounts[0]->businessId);
        $this->assertNull($accounts[0]->accountStatus); // FB-specific, không dùng cho TikTok
        // Header Access-Token được gửi.
        Http::assertSent(fn ($req) => $req->hasHeader('Access-Token', 'AT'));
    }

    public function test_list_entities_campaign_maps_budget_and_status(): void
    {
        Http::fake(['business-api.tiktok.com/*campaign/get*' => Http::response([
            'code' => 0, 'data' => [
                'list' => [[
                    'campaign_id' => 'c1', 'campaign_name' => 'CD', 'operation_status' => 'ENABLE',
                    'secondary_status' => 'CAMPAIGN_STATUS_DELIVERY_OK', 'objective_type' => 'TRAFFIC',
                    'budget' => 100000, 'budget_mode' => 'BUDGET_MODE_DAY',
                ]],
                'page_info' => ['page' => 1, 'page_size' => 1000, 'total_page' => 1, 'total_number' => 1],
            ],
        ], 200)]);

        $rows = $this->connector()->listEntities('AT', '123', 'campaign');

        $this->assertCount(1, $rows);
        $this->assertSame('campaign', $rows[0]->level);
        $this->assertSame('c1', $rows[0]->externalId);
        $this->assertNull($rows[0]->parentExternalId);
        $this->assertSame('CD', $rows[0]->name);
        $this->assertSame('ENABLE', $rows[0]->status);
        $this->assertSame(100000, $rows[0]->dailyBudget);
        $this->assertNull($rows[0]->lifetimeBudget);
        $this->assertSame('TRAFFIC', $rows[0]->objective);
    }

    public function test_list_entities_adgroup_maps_to_adset_level_with_lifetime_budget(): void
    {
        Http::fake(['business-api.tiktok.com/*adgroup/get*' => Http::response([
            'code' => 0, 'data' => [
                'list' => [[
                    'adgroup_id' => 'a1', 'campaign_id' => 'c1', 'adgroup_name' => 'AG',
                    'operation_status' => 'ENABLE', 'optimization_goal' => 'CLICK',
                    'budget' => 5000, 'budget_mode' => 'BUDGET_MODE_TOTAL',
                ]],
                'page_info' => ['total_page' => 1],
            ],
        ], 200)]);

        $rows = $this->connector()->listEntities('AT', '123', 'adset');

        $this->assertSame('adset', $rows[0]->level); // adgroup → adset
        $this->assertSame('a1', $rows[0]->externalId);
        $this->assertSame('c1', $rows[0]->parentExternalId);
        $this->assertSame('CLICK', $rows[0]->optimizationGoal);
        $this->assertSame(5000, $rows[0]->lifetimeBudget);
        $this->assertNull($rows[0]->dailyBudget);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/adgroup/get/'));
    }

    public function test_fetch_insights_maps_report_rows_keyed_by_entity_id(): void
    {
        Http::fake(['business-api.tiktok.com/*report/integrated/get*' => Http::response([
            'code' => 0, 'data' => [
                'list' => [[
                    'dimensions' => ['campaign_id' => 'c1'],
                    'metrics' => [
                        'spend' => '100', 'impressions' => '1000', 'clicks' => '10', 'ctr' => '1.0',
                        'cpc' => '10', 'cpm' => '100', 'reach' => '900', 'conversion' => '5',
                    ],
                ]],
                'page_info' => ['total_page' => 1],
            ],
        ], 200)]);

        $rows = $this->connector()->fetchInsights('AT', '123', 'campaign', ['time_range' => ['since' => '2026-06-01', 'until' => '2026-06-07']]);

        $this->assertCount(1, $rows);
        $this->assertSame('c1', $rows[0]->externalId);
        $this->assertSame(100, $rows[0]->spend);
        $this->assertSame(1000, $rows[0]->impressions);
        $this->assertSame(5, $rows[0]->results);
        $this->assertSame('c1', (string) $rows[0]->raw['campaign_id']); // AdsReportService key
        Http::assertSent(fn ($req) => str_contains($req->url(), 'data_level=AUCTION_CAMPAIGN')
            && str_contains($req->url(), 'start_date=2026-06-01'));
    }

    public function test_capabilities_and_unsupported_write(): void
    {
        $c = $this->connector();
        $this->assertTrue($c->supports('insights.read'));
        $this->assertTrue($c->supports('insights.account_report'));
        $this->assertFalse($c->supports('ads.create'));

        $this->expectException(UnsupportedOperation::class);
        $c->fetchAdCreatives('AT', '123');
    }

    public function test_build_authorization_url_carries_app_id_and_state(): void
    {
        $url = $this->connector()->buildAuthorizationUrl('STATE123');
        $this->assertStringContainsString('portal/auth', $url);
        $this->assertStringContainsString('app_id=A', $url);
        $this->assertStringContainsString('state=STATE123', $url);
    }
}
