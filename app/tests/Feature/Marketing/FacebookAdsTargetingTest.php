<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsTargetingTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_search_targeting_maps_interest_options(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response([
            'data' => [['id' => '6003', 'name' => 'Thời trang', 'audience_size_lower_bound' => 5000000]],
        ], 200)]);

        $opts = $this->connector()->searchTargeting('TOK', 'thời trang');

        $this->assertCount(1, $opts);
        $this->assertSame('6003', $opts[0]->id);
        $this->assertSame('Thời trang', $opts[0]->name);
        $this->assertSame('interests', $opts[0]->type);
        $this->assertSame(5000000, $opts[0]->audienceSize);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/search') && $r->data()['type'] === 'adinterest' && $r->data()['q'] === 'thời trang');
    }

    public function test_search_targeting_labels_behavior_type(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response([
            'data' => [['id' => '7001', 'name' => 'Người mua sắm online']],
        ], 200)]);

        $opts = $this->connector()->searchTargeting('TOK', 'mua sắm', 'adbehavior');

        $this->assertSame('behaviors', $opts[0]->type);
        Http::assertSent(fn ($r) => $r->data()['type'] === 'adbehavior');
    }

    public function test_estimate_audience_maps_bounds(): void
    {
        Http::fake(['graph.facebook.com/*/delivery_estimate*' => Http::response([
            'data' => [['estimate_mau_lower_bound' => 1000000, 'estimate_mau_upper_bound' => 2100000]],
        ], 200)]);

        $size = $this->connector()->estimateAudience('TOK', 'act_1', ['geo_locations' => ['countries' => ['VN']]], 'REACH');

        $this->assertSame(1000000, $size->lowerBound);
        $this->assertSame(2100000, $size->upperBound);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'act_1/delivery_estimate') && $r->data()['optimization_goal'] === 'REACH');
    }
}
