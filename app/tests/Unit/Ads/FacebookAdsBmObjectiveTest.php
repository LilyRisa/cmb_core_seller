<?php

namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsBmObjectiveTest extends TestCase
{
    public function test_list_ad_accounts_includes_business(): void
    {
        Http::fake(['graph.facebook.com/*me/adaccounts*' => Http::response([
            'data' => [['id' => 'act_1', 'name' => 'Shop', 'currency' => 'VND', 'account_status' => 1,
                'business' => ['id' => 'BM1', 'name' => 'My Business']]],
        ], 200)]);

        $a = (new FacebookAdsConnector(['graph_version' => 'v19.0']))->listAdAccounts('AT')[0];
        $this->assertSame('BM1', $a->businessId);
        $this->assertSame('My Business', $a->businessName);
    }

    public function test_list_campaign_includes_objective(): void
    {
        Http::fake(['graph.facebook.com/*act_1/campaigns*' => Http::response([
            'data' => [['id' => 'C1', 'name' => 'Camp', 'status' => 'ACTIVE', 'objective' => 'OUTCOME_SALES', 'daily_budget' => '100000']],
        ], 200)]);

        $e = (new FacebookAdsConnector(['graph_version' => 'v19.0']))->listEntities('AT', 'act_1', 'campaign')[0];
        $this->assertSame('OUTCOME_SALES', $e->objective);
    }
}
