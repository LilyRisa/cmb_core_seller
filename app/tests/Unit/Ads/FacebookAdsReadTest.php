<?php

namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsReadTest extends TestCase
{
    private function conn(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_list_ad_accounts(): void
    {
        Http::fake(['graph.facebook.com/*me/adaccounts*' => Http::response([
            'data' => [['account_id' => '123', 'id' => 'act_123', 'name' => 'Shop', 'currency' => 'VND', 'account_status' => 1]],
        ], 200)]);
        $accts = $this->conn()->listAdAccounts('AT');
        $this->assertCount(1, $accts);
        $this->assertSame('act_123', $accts[0]->externalAccountId);
        $this->assertSame('VND', $accts[0]->currency);
    }

    public function test_list_campaign_entities(): void
    {
        Http::fake(['graph.facebook.com/*act_123/campaigns*' => Http::response([
            'data' => [['id' => 'C1', 'name' => 'Camp', 'status' => 'ACTIVE', 'effective_status' => 'ACTIVE', 'daily_budget' => '100000']],
        ], 200)]);
        $items = $this->conn()->listEntities('AT', 'act_123', 'campaign');
        $this->assertSame('C1', $items[0]->externalId);
        $this->assertSame(100000, $items[0]->dailyBudget);
        $this->assertSame('campaign', $items[0]->level);
    }

    public function test_list_adset_links_parent(): void
    {
        Http::fake(['graph.facebook.com/*act_123/adsets*' => Http::response([
            'data' => [['id' => 'AS1', 'name' => 'Set', 'status' => 'ACTIVE', 'campaign_id' => 'C1']],
        ], 200)]);
        $items = $this->conn()->listEntities('AT', 'act_123', 'adset');
        $this->assertSame('AS1', $items[0]->externalId);
        $this->assertSame('C1', $items[0]->parentExternalId);
    }
}
