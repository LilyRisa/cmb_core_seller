<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\MarketingAiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calls the active `marketing_ai_providers` LLM for marketing analysis. Adapters:
 * anthropic (Messages API), openai_compatible (Chat Completions). No active
 * provider / adapter `manual` / any failure ⇒ deterministic stub (dev/test,
 * 0 quota). Does NOT touch Integrations/Ai or messaging.
 */
class LlmMarketingAnalysisClient implements MarketingAnalysisClient
{
    public function analyze(array $data, string $instruction): array
    {
        $provider = MarketingAiProvider::query()->where('is_active', true)->first();

        if ($provider === null || $provider->adapter === 'manual') {
            return ['payload' => $this->stub($data), 'provider_code' => $provider?->code, 'model' => $provider?->default_model];
        }

        try {
            $payload = match ($provider->adapter) {
                'anthropic' => $this->anthropic($provider, $data, $instruction),
                'openai_compatible' => $this->openai($provider, $data, $instruction),
                default => $this->stub($data),
            };

            return ['payload' => $payload, 'provider_code' => $provider->code, 'model' => $provider->default_model];
        } catch (\Throwable $e) {
            Log::warning('marketing.forecast.ai_failed', ['provider' => $provider->code, 'error' => $e->getMessage()]);

            return ['payload' => $this->stub($data), 'provider_code' => $provider->code, 'model' => $provider->default_model];
        }
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function anthropic(MarketingAiProvider $p, array $data, string $instruction): array
    {
        $base = rtrim($p->base_url ?: 'https://api.anthropic.com', '/');
        $res = Http::timeout(60)->withHeaders([
            'x-api-key' => (string) $p->api_key, 'anthropic-version' => '2023-06-01',
        ])->post($base.'/v1/messages', [
            'model' => $p->default_model ?: 'claude-3-5-sonnet-latest',
            'max_tokens' => 1024,
            'messages' => [['role' => 'user', 'content' => $this->prompt($data, $instruction)]],
        ]);
        $text = (string) ($res->json('content.0.text') ?? '');

        return $this->parseJson($text, $data);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function openai(MarketingAiProvider $p, array $data, string $instruction): array
    {
        $base = rtrim($p->base_url ?: 'https://api.openai.com/v1', '/');
        $res = Http::timeout(60)->withToken((string) $p->api_key)->post($base.'/chat/completions', [
            'model' => $p->default_model ?: 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [['role' => 'user', 'content' => $this->prompt($data, $instruction)]],
        ]);
        $text = (string) ($res->json('choices.0.message.content') ?? '');

        return $this->parseJson($text, $data);
    }

    /** @param array<string,mixed> $data */
    private function prompt(array $data, string $instruction): string
    {
        return $instruction."\n\nDỮ LIỆU (JSON):\n".json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ."\n\nCHỈ trả về JSON đúng schema {forecast:{next_7d:{conversations,orders,spend,projected_cost_per_order}}, strategy:[{action,campaign,rationale,confidence}], creative_review:[{ref,name,verdict,issues:[string],suggestions:[string]}]}. "
            .'creative_review: với MỖI quảng cáo/bài post trong dữ liệu, đánh giá nội dung đã tối ưu chưa (dựa trên text + tương tác + hiệu suất), verdict "tốt" hoặc "cần cải thiện".';
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function parseJson(string $text, array $data): array
    {
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json) && isset($json['forecast'])) {
                return $json;
            }
        }

        return $this->stub($data);
    }

    /**
     * Deterministic projection from reconciliation rows (no AI) — dev/test/fallback.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function stub(array $data): array
    {
        $rows = array_values(array_filter((array) ($data['rows'] ?? []), 'is_array'));
        $n = max(1, count($rows));
        $sum = fn (string $k) => array_sum(array_map(fn ($r) => (int) ($r[$k] ?? 0), $rows));
        $avgConv = $sum('conversations') / $n;
        $avgOrders = $sum('manual_orders') / $n;
        $avgSpend = $sum('spend') / $n;
        $next7Orders = (int) round($avgOrders * 7);

        $creatives = array_values(array_filter((array) ($data['creatives'] ?? []), 'is_array'));
        $review = array_map(fn (array $c) => [
            'ref' => (string) ($c['ad_id'] ?? $c['post_id'] ?? ''),
            'name' => (string) ($c['name'] ?? ''),
            'verdict' => 'cần xem xét',
            'issues' => [],
            'suggestions' => ['Thêm lời kêu gọi hành động rõ ràng và hình ảnh/đoạn mở đầu nổi bật.'],
        ], $creatives);

        return [
            'forecast' => [
                'next_7d' => [
                    'conversations' => (int) round($avgConv * 7),
                    'orders' => $next7Orders,
                    'spend' => (int) round($avgSpend * 7),
                    'projected_cost_per_order' => $next7Orders > 0 ? (int) round($avgSpend * 7 / $next7Orders) : null,
                ],
            ],
            'strategy' => [[
                'action' => $avgOrders > 0 ? 'maintain_budget' : 'review_targeting',
                'campaign' => null,
                'rationale' => 'Dự báo deterministic từ trung bình '.$n.' ngày gần nhất (chưa cấu hình provider AI marketing).',
                'confidence' => 0.4,
            ]],
            'creative_review' => $review,
            'generated_by' => 'stub',
        ];
    }
}
