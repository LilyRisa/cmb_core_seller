<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\ShopReportConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contract test cho năng lực "Báo cáo sàn" (ShopReportConnector) của 3 sàn — Http::fake,
 * không gọi mạng thật. Khẳng định mapping raw → ShopHealthDTO/PenaltyPointDTO/PunishmentDTO
 * và UnsupportedOperation cho sàn không có API điểm phạt. SPEC 2026-06-06-shop-report-multi-channel.
 */
class ShopReportConnectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.lazada.app_key' => 'lk', 'integrations.lazada.app_secret' => 'ls',
            'integrations.shopee.partner_id' => 123, 'integrations.shopee.partner_key' => 'sk',
            'integrations.shopee.base_url' => 'https://partner.shopeemobile.com',
            'integrations.tiktok.app_key' => 'tk', 'integrations.tiktok.app_secret' => 'ts',
            'integrations.tiktok.base_url' => 'https://open-api.tiktokglobalshop.com',
        ]);
        $reg = app(ChannelRegistry::class);
        $reg->register('lazada', LazadaConnector::class);
        $reg->register('shopee', ShopeeConnector::class);
        $reg->register('tiktok', TikTokConnector::class);
    }

    private function connector(string $provider): ShopReportConnector
    {
        $c = app(ChannelRegistry::class)->for($provider);
        $this->assertInstanceOf(ShopReportConnector::class, $c);

        return $c;
    }

    private function auth(string $provider, string $shopId): AuthContext
    {
        return new AuthContext(
            channelAccountId: 1, provider: $provider, externalShopId: $shopId, accessToken: 'at',
            extra: $provider === 'tiktok' ? ['shop_cipher' => 'cipher'] : [],
        );
    }

    public function test_all_three_connectors_support_report_health(): void
    {
        $this->assertTrue($this->connector('lazada')->supports('report.health'));
        $this->assertTrue($this->connector('shopee')->supports('report.health'));
        $this->assertTrue($this->connector('tiktok')->supports('report.health'));
        // Chỉ Shopee có API điểm phạt.
        $this->assertTrue($this->connector('shopee')->supports('report.penalty'));
        $this->assertFalse($this->connector('lazada')->supports('report.penalty'));
        $this->assertFalse($this->connector('tiktok')->supports('report.penalty'));
    }

    public function test_lazada_maps_seller_performance_to_health(): void
    {
        Http::fake(['*/seller/performance/get*' => Http::response([
            'code' => '0', 'data' => [
                'seller_id' => '42', 'main_category_name' => 'Đồ gia dụng',
                'indicators' => [[
                    'type' => 'POSITIVE_SELLER_RATING', 'name' => 'Đánh giá tích cực',
                    'score' => '92.0', 'score_format' => 'PERCENTAGE',
                    'target' => '85.0', 'target_format' => 'GREATER_THAN_PERCENTAGE', 'target_respected' => 'true',
                ], [
                    'type' => 'SHIP_ON_TIME', 'name' => 'Giao đúng hạn',
                    'score' => '80.0', 'score_format' => 'PERCENTAGE',
                    'target' => '95.0', 'target_format' => 'GREATER_THAN_PERCENTAGE', 'target_respected' => 'false',
                ]],
            ],
        ])]);

        $health = $this->connector('lazada')->fetchShopHealth($this->auth('lazada', 'VNSHOP'));

        $this->assertSame('lazada', $health->provider);
        $this->assertSame('health', $health->kind);
        $this->assertNull($health->overallRating);
        $this->assertCount(2, $health->metrics);
        $this->assertSame(92.0, $health->metrics[0]->value);
        $this->assertSame('percent', $health->metrics[0]->unit);
        $this->assertTrue($health->metrics[0]->passed);
        $this->assertFalse($health->metrics[1]->passed);
        $this->assertSame('fulfillment', $health->metrics[1]->group);
    }

    public function test_lazada_penalty_is_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector('lazada')->fetchPenaltyPoints($this->auth('lazada', 'VNSHOP'));
    }

    public function test_shopee_maps_account_health_and_penalty(): void
    {
        Http::fake([
            '*/account_health/get_shop_performance*' => Http::response(['response' => [
                'overall_performance' => ['rating' => 3, 'fulfillment_failed' => 1, 'listing_failed' => 0, 'custom_service_failed' => 0],
                'metric_list' => [
                    ['metric_type' => 1, 'metric_id' => 1, 'metric_name' => 'Tỉ lệ giao trễ', 'current_period' => 2.5, 'unit' => 2, 'target' => ['value' => 5, 'comparator' => '<=']],
                    ['metric_type' => 1, 'metric_id' => -1, 'metric_name' => 'Nhóm', 'current_period' => 0, 'unit' => 1],
                ],
            ]]),
            '*/account_health/get_penalty_point_history*' => Http::response(['response' => [
                'penalty_point_list' => [['latest_point_num' => 2, 'original_point_num' => 3, 'violation_type' => 5, 'issue_time' => 1700000000, 'reference_id' => 'r1']],
                'total_count' => 1,
            ]]),
            '*/account_health/get_punishment_history*' => Http::response(['response' => [
                'punishment_list' => [['punishment_type' => 109, 'reason' => 3, 'start_time' => 1700000000, 'end_time' => 1701000000]],
                'total_count' => 1,
            ]]),
        ]);

        $c = $this->connector('shopee');
        $auth = $this->auth('shopee', '600001');

        $health = $c->fetchShopHealth($auth);
        $this->assertSame(3, $health->overallRating);
        $this->assertSame('Tốt', $health->overallLabel);
        $this->assertCount(1, $health->metrics);                 // metric_id < 0 bị loại
        $this->assertTrue($health->metrics[0]->passed);          // 2.5 <= 5
        $this->assertSame('percent', $health->metrics[0]->unit);

        $penalties = $c->fetchPenaltyPoints($auth);
        $this->assertSame(2, $penalties[0]->points);
        $this->assertSame('Tỉ lệ giao trễ cao', $penalties[0]->violationLabel);

        $punishments = $c->fetchPunishments($auth);
        $this->assertSame('Tài khoản bị treo', $punishments[0]->typeLabel);
        $this->assertSame(3, $punishments[0]->tier);
    }

    public function test_tiktok_maps_shop_performance_gmv(): void
    {
        Http::fake(['*/analytics/*/shop/performance*' => Http::response([
            'code' => 0, 'data' => ['performance' => ['intervals' => [[
                'start_date' => '2026-05-30', 'end_date' => '2026-06-06',
                'sales' => [
                    'gmv' => ['overall' => ['amount' => '1500000', 'currency' => 'VND']],
                    'orders' => ['overall' => 42],
                ],
            ]]]],
        ])]);

        $health = $this->connector('tiktok')->fetchShopHealth($this->auth('tiktok', 'TTSHOP'));

        $this->assertSame('performance', $health->kind);
        $byKey = collect($health->metrics)->keyBy('key');
        $this->assertSame(1500000.0, $byKey['gmv']->value);
        $this->assertSame('money', $byKey['gmv']->unit);
        $this->assertSame(42.0, $byKey['orders']->value);
        $this->assertSame('number', $byKey['orders']->unit);
    }

    public function test_tiktok_penalty_is_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector('tiktok')->fetchPunishments($this->auth('tiktok', 'TTSHOP'));
    }
}
