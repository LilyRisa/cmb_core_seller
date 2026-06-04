<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FacebookAdsConnectorTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    /**
     * Insights breakdown rows must carry the level's id so the caller can key
     * them back to the right entity. Graph only returns campaign_id/adset_id/ad_id
     * when explicitly requested in `fields` (v19+ dropped implicit fields).
     */
    #[DataProvider('levelIdFieldProvider')]
    public function test_fetch_insights_requests_level_id_field(string $level, string $idField): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => []], 200)]);

        $this->connector()->fetchInsights('token', 'act_1', $level, ['date_preset' => 'today']);

        Http::assertSent(function ($request) use ($idField) {
            $fields = explode(',', (string) ($request->data()['fields'] ?? ''));

            return in_array($idField, $fields, true);
        });
    }

    /** @return array<string, array{0:string,1:string}> */
    public static function levelIdFieldProvider(): array
    {
        return [
            'campaign' => ['campaign', 'campaign_id'],
            'adset' => ['adset', 'adset_id'],
            'ad' => ['ad', 'ad_id'],
        ];
    }

    public function test_fetch_insights_account_level_omits_entity_id_field(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => []], 200)]);

        $this->connector()->fetchInsights('token', 'act_1', 'account', ['date_preset' => 'today']);

        Http::assertSent(function ($request) {
            $fields = explode(',', (string) ($request->data()['fields'] ?? ''));

            return ! in_array('campaign_id', $fields, true)
                && ! in_array('adset_id', $fields, true)
                && ! in_array('ad_id', $fields, true);
        });
    }
}
