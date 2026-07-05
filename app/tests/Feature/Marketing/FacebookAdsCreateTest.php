<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsCreateTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_create_campaign_posts_mapped_objective_and_returns_id(): void
    {
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['id' => 'C_NEW'], 200)]);

        $id = $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(objective: 'messages', name: 'Camp'));

        $this->assertSame('C_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();

            return str_contains($request->url(), 'act_1/campaigns')
                && $d['objective'] === 'OUTCOME_ENGAGEMENT'
                && $d['status'] === 'PAUSED'
                && array_key_exists('special_ad_categories', $d);
        });
    }

    public function test_uses_latest_graph_version_regardless_of_config(): void
    {
        // Version cố định trong code (không qua env) — luôn gọi bản mới nhất.
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'C'], 200)]);
        $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(objective: 'traffic', name: 'X'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com/v25.0/'));
    }

    public function test_create_adset_scales_budget_and_sets_messaging_spec(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS_NEW'], 200)]);

        $id = $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C_NEW', objective: 'messages',
            dailyBudgetMajor: 150000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]],
            pageId: '123', startTime: null,
        ));

        $this->assertSame('AS_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();
            $promoted = json_decode($d['promoted_object'] ?? '{}', true);

            return str_contains($request->url(), 'act_1/adsets')
                && $d['campaign_id'] === 'C_NEW'
                && $d['daily_budget'] === '150000'
                && $d['optimization_goal'] === 'CONVERSATIONS'
                && $d['billing_event'] === 'IMPRESSIONS'
                && $d['destination_type'] === 'MESSENGER'
                && ($promoted['page_id'] ?? null) === '123';
        });
    }

    public function test_create_adset_strips_deprecated_video_feeds_position(): void
    {
        // Meta khai tử vị trí `video_feeds` ở Graph v19 ⇒ gửi lên là cả ad set bị reject
        // (code 100/subcode 2490562). Connector phải tự gỡ vị trí khai tử.
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS_VF'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C', objective: 'traffic',
            dailyBudgetMajor: 100000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]],
            pageId: '123', startTime: null,
            placementConfig: [
                'automatic' => false,
                'publisher_platforms' => ['facebook'],
                'positions' => ['facebook' => ['feed', 'video_feeds', 'story']],
            ],
        ));

        Http::assertSent(function ($r) {
            $fb = json_decode($r->data()['targeting'], true)['facebook_positions'] ?? [];

            return ! in_array('video_feeds', $fb, true)
                && in_array('feed', $fb, true)
                && in_array('story', $fb, true);
        });
    }

    public function test_create_adset_drops_desktop_only_position_when_mobile_only(): void
    {
        // `right_hand_column` chỉ chạy desktop — nếu device_platforms chỉ mobile, FB reject.
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS_RHC'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C', objective: 'traffic',
            dailyBudgetMajor: 100000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]],
            pageId: '123', startTime: null,
            placementConfig: [
                'automatic' => false,
                'device_platforms' => ['mobile'],
                'publisher_platforms' => ['facebook'],
                'positions' => ['facebook' => ['feed', 'right_hand_column']],
            ],
        ));

        Http::assertSent(function ($r) {
            $fb = json_decode($r->data()['targeting'], true)['facebook_positions'] ?? [];

            return ! in_array('right_hand_column', $fb, true) && in_array('feed', $fb, true);
        });
    }

    public function test_create_ad_from_existing_page_post_uses_object_story_id(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_NEW'], 200)]);

        $id = $this->connector()->createAd('tok', 'act_1', new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS_NEW', pageId: '123',
            pagePostId: '123_456', cta: 'MESSAGE_PAGE',
        ));

        $this->assertSame('AD_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();
            $creative = json_decode($d['creative'] ?? '{}', true);

            return str_contains($request->url(), 'act_1/ads')
                && $d['adset_id'] === 'AS_NEW'
                && ($creative['object_story_id'] ?? null) === '123_456';
        });
    }

    public function test_create_ad_new_creative_uses_object_story_spec(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_NEW2'], 200)]);

        $this->connector()->createAd('tok', 'act_1', new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS_NEW', pageId: '123',
            imageHash: 'HASH', primaryText: 'Mua ngay', headline: 'Sale', linkUrl: 'https://shop.vn', cta: 'SHOP_NOW',
        ));

        Http::assertSent(function ($request) {
            $creative = json_decode($request->data()['creative'] ?? '{}', true);
            $spec = $creative['object_story_spec'] ?? [];

            return ($spec['page_id'] ?? null) === '123'
                && ($spec['link_data']['image_hash'] ?? null) === 'HASH'
                && ($spec['link_data']['call_to_action']['type'] ?? null) === 'SHOP_NOW';
        });
    }

    public function test_create_ad_does_not_send_deprecated_standard_enhancements(): void
    {
        // Cờ gộp standard_enhancements (OPT_IN) đã bị Meta ngừng (subcode 3858504) ⇒ dù nháp bật,
        // connector KHÔNG gửi degrees_of_freedom_spec (nếu gửi FB reject cả createAd).
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_E'], 200)]);

        $this->connector()->createAd('tok', 'act_1', new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS', pageId: '123', pagePostId: '123_456',
            standardEnhancements: true,
        ));

        Http::assertSent(fn ($r) => ! array_key_exists('degrees_of_freedom_spec', json_decode($r->data()['creative'], true)));
    }

    public function test_create_ad_without_enhancements_omits_degrees_of_freedom(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_N'], 200)]);

        $this->connector()->createAd('tok', 'act_1', new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS', pageId: '123', pagePostId: '123_456',
        ));

        Http::assertSent(fn ($r) => ! array_key_exists('degrees_of_freedom_spec', json_decode($r->data()['creative'], true)));
    }

    public function test_create_campaign_throws_on_graph_error(): void
    {
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('createCampaign failed');
        $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(objective: 'messages', name: 'Camp'));
    }

    public function test_create_adset_throws_on_graph_error(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('createAdSet failed');
        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C_NEW', objective: 'messages',
            dailyBudgetMajor: 150000, currency: 'VND', targeting: [], pageId: '123',
        ));
    }

    public function test_create_adset_throws_when_messaging_objective_missing_page_id(): void
    {
        // needs_promoted_object objectives require a pageId — guard before any HTTP call.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires pageId');
        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C_NEW', objective: 'messages',
            dailyBudgetMajor: 150000, currency: 'VND', targeting: [], pageId: null,
        ));
    }

    public function test_create_ad_throws_on_graph_error(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('createAd failed');
        $this->connector()->createAd('tok', 'act_1', new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS_NEW', pageId: '123', pagePostId: '123_456',
        ));
    }

    public function test_create_campaign_with_cbo_sends_budget_and_bid_strategy(): void
    {
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['id' => 'C_CBO'], 200)]);

        $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(
            objective: 'messages', name: 'Camp', dailyBudgetMajor: 300000, currency: 'VND',
        ));

        Http::assertSent(function ($r) {
            $d = $r->data();

            return ($d['daily_budget'] ?? null) === '300000' && ($d['bid_strategy'] ?? null) === 'LOWEST_COST_WITHOUT_CAP';
        });
    }

    public function test_create_campaign_without_budget_omits_it(): void
    {
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['id' => 'C'], 200)]);

        $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(objective: 'messages', name: 'Camp'));

        Http::assertSent(fn ($r) => ! array_key_exists('daily_budget', $r->data()));
    }

    public function test_create_campaign_without_budget_sets_adset_budget_sharing_flag(): void
    {
        // Graph requires is_adset_budget_sharing_enabled when there's no campaign budget.
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['id' => 'C'], 200)]);

        $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(objective: 'messages', name: 'Camp'));

        Http::assertSent(fn ($r) => ($r->data()['is_adset_budget_sharing_enabled'] ?? null) === 'false');
    }

    public function test_create_campaign_with_cbo_omits_adset_budget_sharing_flag(): void
    {
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['id' => 'C'], 200)]);

        $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(
            objective: 'messages', name: 'Camp', dailyBudgetMajor: 300000, currency: 'VND',
        ));

        Http::assertSent(fn ($r) => ! array_key_exists('is_adset_budget_sharing_enabled', $r->data()));
    }

    public function test_create_adset_with_own_budget_sets_bid_strategy(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 100000, currency: 'VND', targeting: [], pageId: '123',
        ));

        Http::assertSent(fn ($r) => ($r->data()['bid_strategy'] ?? null) === 'LOWEST_COST_WITHOUT_CAP');
    }

    public function test_create_adset_omits_daily_budget_when_zero_cbo(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 0, currency: 'VND', targeting: [], pageId: '123',
        ));

        Http::assertSent(fn ($r) => ! array_key_exists('daily_budget', $r->data()));
    }

    public function test_create_adset_merges_manual_placements_into_targeting(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: ['geo_locations' => ['countries' => ['VN']]], pageId: '123',
            placementConfig: ['automatic' => false, 'device_platforms' => ['mobile'], 'publisher_platforms' => ['facebook', 'instagram'], 'positions' => ['facebook' => ['feed', 'facebook_reels'], 'instagram' => ['story']]],
        ));

        Http::assertSent(function ($r) {
            $t = json_decode($r->data()['targeting'], true);

            return $t['geo_locations']['countries'] === ['VN']
                && $t['device_platforms'] === ['mobile']
                && $t['publisher_platforms'] === ['facebook', 'instagram']
                && $t['facebook_positions'] === ['feed', 'facebook_reels']
                && $t['instagram_positions'] === ['story'];
        });
    }

    public function test_create_adset_defaults_advantage_audience_opt_out(): void
    {
        // v23.0+: Graph REQUIRES targeting.targeting_automation.advantage_audience (1|0) khi tạo ad set mới
        // (thiếu ⇒ code 100/subcode 1870227). Mặc định 0 = tôn trọng đúng targeting người bán chọn.
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]], pageId: '123',
        ));

        Http::assertSent(function ($r) {
            $t = json_decode($r->data()['targeting'], true);

            return ($t['targeting_automation']['advantage_audience'] ?? null) === 0
                && $t['geo_locations']['countries'] === ['VN'];
        });
    }

    public function test_create_adset_preserves_explicit_advantage_audience_opt_in(): void
    {
        // Nếu người bán chủ động bật Advantage+ audience (=1) trong targeting, connector giữ nguyên.
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']], 'targeting_automation' => ['advantage_audience' => 1]],
            pageId: '123',
        ));

        Http::assertSent(fn ($r) => (json_decode($r->data()['targeting'], true)['targeting_automation']['advantage_audience'] ?? null) === 1);
    }

    public function test_create_adset_conversions_uses_pixel_promoted_object(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'conversions',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: [],
            pixelId: 'PX1', conversionEvent: 'PURCHASE',
        ));

        Http::assertSent(function ($r) {
            $d = $r->data();
            $po = json_decode($d['promoted_object'] ?? '{}', true);

            return $d['optimization_goal'] === 'OFFSITE_CONVERSIONS'
                && ($po['pixel_id'] ?? null) === 'PX1'
                && ($po['custom_event_type'] ?? null) === 'PURCHASE';
        });
    }

    public function test_create_adset_conversions_defaults_event_to_purchase(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'conversions',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: [], pixelId: 'PX1',
        ));

        Http::assertSent(fn ($r) => (json_decode($r->data()['promoted_object'], true)['custom_event_type'] ?? null) === 'PURCHASE');
    }

    public function test_create_adset_conversions_throws_without_pixel(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires pixelId');
        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'conversions',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: [], pixelId: null,
        ));
    }

    public function test_list_pixels_maps_id_and_name(): void
    {
        Http::fake(['graph.facebook.com/*/act_1/adspixels*' => Http::response(['data' => [
            ['id' => 'PX1', 'name' => 'Pixel chính'],
            ['id' => 'PX2'],
        ]], 200)]);

        $out = $this->connector()->listPixels('tok', 'act_1');

        $this->assertSame('PX1', $out[0]->id);
        $this->assertSame('Pixel chính', $out[0]->name);
        $this->assertSame('PX2', $out[1]->name); // falls back to id
    }

    public function test_create_adset_sends_end_time_when_set(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: [], pageId: '123',
            startTime: '2026-07-01T00:00:00+00:00', endTime: '2026-07-10T00:00:00+00:00',
        ));

        Http::assertSent(fn ($r) => ($r->data()['end_time'] ?? null) === '2026-07-10T00:00:00+00:00'
            && ($r->data()['start_time'] ?? null) === '2026-07-01T00:00:00+00:00');
    }

    public function test_create_adset_omits_end_time_when_null(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: [], pageId: '123',
        ));

        Http::assertSent(fn ($r) => ! array_key_exists('end_time', $r->data()));
    }

    public function test_create_adset_automatic_placements_not_merged(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: [], pageId: '123',
            placementConfig: ['automatic' => true, 'publisher_platforms' => ['facebook']],
        ));

        Http::assertSent(function ($r) {
            $t = json_decode($r->data()['targeting'], true);

            return ! array_key_exists('publisher_platforms', $t);
        });
    }

    public function test_search_targeting_geo_uses_adgeolocation_and_location_types(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response(['data' => [
            ['key' => 'VN', 'name' => 'Vietnam', 'type' => 'country'],
            ['key' => '3658', 'name' => 'Hanoi', 'type' => 'region', 'country_code' => 'VN', 'country_name' => 'Vietnam'],
            ['key' => '1006824', 'name' => 'Hanoi', 'type' => 'city', 'region' => 'Hanoi', 'country_code' => 'VN'],
        ]], 200)]);

        $out = $this->connector()->searchTargeting('tok', 'Hanoi', 'adgeolocation');

        Http::assertSent(function ($r) {
            return str_contains($r->url(), 'type=adgeolocation')
                && str_contains(urldecode($r->url()), 'location_types');
        });
        $this->assertCount(3, $out);
        $this->assertSame('VN', $out[0]->id);
        $this->assertSame('country', $out[0]->type);
        $this->assertSame('3658', $out[1]->id);
        $this->assertSame('region', $out[1]->type);
        $this->assertSame('1006824', $out[2]->id);
        $this->assertSame('city', $out[2]->type);
    }

    public function test_search_targeting_detailed_uses_per_result_type(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response(['data' => [
            ['id' => '111', 'name' => 'Cà phê', 'type' => 'interests', 'audience_size_lower_bound' => 5000],
            ['id' => '222', 'name' => 'Người đi du lịch thường xuyên', 'type' => 'behaviors'],
            ['id' => '333', 'name' => 'Cha mẹ', 'type' => 'family_statuses'],
        ]], 200)]);

        $out = $this->connector()->searchTargeting('tok', 'x', 'adTargetingCategory');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'type=adTargetingCategory'));
        $this->assertSame('interests', $out[0]->type);
        $this->assertSame('behaviors', $out[1]->type);
        $this->assertSame('family_statuses', $out[2]->type);
        $this->assertSame(5000, $out[0]->audienceSize);
    }

    public function test_search_targeting_interest_unchanged(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response(['data' => [
            ['id' => '123', 'name' => 'Coffee', 'audience_size_lower_bound' => 1000],
        ]], 200)]);

        $out = $this->connector()->searchTargeting('tok', 'coffee');

        $this->assertSame('123', $out[0]->id);
        $this->assertSame('interests', $out[0]->type);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'type=adinterest'));
    }
}
