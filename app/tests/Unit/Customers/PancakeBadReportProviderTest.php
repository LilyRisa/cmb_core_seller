<?php

namespace Tests\Unit\Customers;

use CMBcoreSeller\Integrations\Pancake\PancakeBadReportProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0038 — map response Pancake `bad_report_info` → BadReportData + fail-soft.
 */
class PancakeBadReportProviderTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE = [
        'data' => [
            'reports_by_phone' => [
                '+84395151515' => ['order_fail' => 4, 'order_success' => 8, 'warning' => 3],
            ],
            'available_for_report' => ['+84395151515'],
            'warning_phone_number' => [
                ['id' => 'a', 'reason' => 'bom hàng con lợn này', 'inserted_at' => '2026-01-18T05:51:04', 'phone_number' => '+84395151515', 'reported_by' => ['fb_id' => 'x', 'fb_name' => 'Ha']],
                ['id' => 'b', 'reason' => 'boom', 'inserted_at' => '2025-11-04T11:22:47', 'phone_number' => '+84395151515', 'reported_by' => ['fb_id' => 'y', 'fb_name' => 'Z']],
            ],
        ],
        'success' => true,
    ];

    private function provider(array $overrides = []): PancakeBadReportProvider
    {
        return new PancakeBadReportProvider(array_merge([
            'enabled' => true,
            'shop_id' => '1720000852',
            'access_token' => 'tok',
            'api_base_url' => 'https://pos.pancake.vn/api/v1',
            'http' => ['timeout' => 5, 'retries' => 0],
        ], $overrides));
    }

    public function test_maps_sample_payload_and_sends_e164_phone(): void
    {
        Http::fake(['pos.pancake.vn/*' => Http::response(self::SAMPLE, 200)]);

        $data = $this->provider()->lookup('0395151515');

        $this->assertNotNull($data);
        $this->assertTrue($data->matched);
        $this->assertSame(4, $data->orderFail);
        $this->assertSame(8, $data->orderSuccess);
        $this->assertSame(3, $data->warningCount);
        $this->assertCount(2, $data->warnings);
        $this->assertSame('bom hàng con lợn này', $data->warnings[0]['reason']);
        $this->assertSame('2026-01-18T05:51:04', $data->warnings[0]['reported_at']);
        // reported_by / page_id / id KHÔNG được giữ
        $this->assertSame(['reason', 'reported_at'], array_keys($data->warnings[0]));

        Http::assertSent(fn ($req) => $req['phone_number'] === '+84395151515' && $req['access_token'] === 'tok'
            && str_contains($req->url(), '/shops/1720000852/orders/bad_report_info'));
    }

    public function test_success_but_phone_not_in_report_returns_clean(): void
    {
        Http::fake(['pos.pancake.vn/*' => Http::response(['data' => ['reports_by_phone' => [], 'warning_phone_number' => []], 'success' => true], 200)]);

        $data = $this->provider()->lookup('0999888777');

        $this->assertNotNull($data);
        $this->assertTrue($data->matched);
        $this->assertFalse($data->hasData());
    }

    public function test_disabled_returns_null_without_calling_http(): void
    {
        Http::fake();
        $this->assertNull($this->provider(['enabled' => false])->lookup('0395151515'));
        Http::assertNothingSent();
    }

    public function test_missing_credentials_returns_null(): void
    {
        Http::fake();
        $this->assertNull($this->provider(['access_token' => ''])->lookup('0395151515'));
        Http::assertNothingSent();
    }

    public function test_http_error_and_unsuccessful_return_null(): void
    {
        Http::fake(['pos.pancake.vn/*' => Http::response('boom', 500)]);
        $this->assertNull($this->provider()->lookup('0395151515'));

        Http::fake(['pos.pancake.vn/*' => Http::response(['success' => false], 200)]);
        $this->assertNull($this->provider()->lookup('0395151515'));
    }
}
