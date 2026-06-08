<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\MarketingAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0036-followup — phân tích AI quảng cáo Facebook lỗi "Client closed the connection
 * before the stream finished": HTTP timeout 60s quá ngắn (non-streaming, buffer cả
 * response) + KHÔNG retry. Fix: timeout cấu hình lớn hơn + connectTimeout (fail-fast khi
 * provider chết) + retry transient.
 */
class LlmMarketingAnalysisTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private function activeAnthropic(): void
    {
        MarketingAiProvider::create([
            'code' => 'cmb', 'adapter' => 'anthropic', 'api_key' => 'k',
            'base_url' => 'https://api.anthropic.com', 'default_model' => 'claude-x', 'is_active' => true,
        ]);
    }

    private static function textResponse(): array
    {
        return ['content' => [['type' => 'text', 'text' => '{"forecast":{"next_7d":{"orders":3}}}']]];
    }

    public function test_config_defaults_give_generous_timeout_and_connect_timeout(): void
    {
        // Timeout tổng phải đủ lớn cho generation chậm; connect timeout ngắn để fail-fast.
        $this->assertGreaterThanOrEqual(120, (int) config('marketing.ai.http_timeout'));
        $this->assertSame(10, (int) config('marketing.ai.http_connect_timeout'));
        $this->assertGreaterThanOrEqual(1, (int) config('marketing.ai.http_retries'));
    }

    public function test_transient_connection_drop_is_retried_then_succeeds(): void
    {
        // Đây CHÍNH là lỗi gặp phải: kết nối bị đóng giữa chừng (timeout/đứt) ⇒ ConnectionException.
        // retry phải bắt và thử lại; nếu KHÔNG có retry (trước fix) ⇒ rơi về stub.
        config(['marketing.ai.http_retries' => 1, 'marketing.ai.http_retry_backoff_ms' => 1]);
        $this->activeAnthropic();
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            if ($calls === 1) {
                throw new ConnectionException('Client closed the connection before the stream finished.');
            }

            return Http::response(self::textResponse(), 200);
        });

        $res = app(MarketingAnalysisClient::class)->analyze(['rows' => []], 'phân tích');

        $this->assertArrayHasKey('forecast', $res['payload']);
        $this->assertArrayNotHasKey('generated_by', $res['payload']); // hồi phục, KHÔNG rơi về stub
        $this->assertSame('cmb', $res['provider_code']);
    }

    public function test_successful_call_returns_parsed_payload(): void
    {
        $this->activeAnthropic();
        Http::fake(['*/v1/messages' => Http::response(self::textResponse(), 200)]);

        $res = app(MarketingAnalysisClient::class)->analyze(['rows' => []], 'phân tích');

        $this->assertSame(3, $res['payload']['forecast']['next_7d']['orders']);
    }
}
