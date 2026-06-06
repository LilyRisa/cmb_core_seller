<?php

namespace CMBcoreSeller\Modules\Channels\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phân tích AI "Báo cáo sàn": chấm điểm sức khỏe gian hàng + khuyến nghị cụ thể.
 *
 * Luôn trả phân tích **deterministic** (rule-based) tin cậy; nếu tenant có gói AI + còn lượt +
 * có AI provider (Claude) thì bổ sung **nhận định ngôn ngữ tự nhiên** (consume 1 lượt). Lỗi/không
 * có provider → degrade về rule, không chặn. SPEC 2026-06-06.
 */
class ShopHealthAnalysisService
{
    public function __construct(private readonly AiCreditMeter $credits) {}

    /**
     * @param  array<string,mixed>  $shopData  metrics/penalties/punishments/recent_penalty_events + overall_label
     * @return array<string,mixed>
     */
    public function analyze(int $tenantId, array $shopData): array
    {
        $result = $this->rules($shopData);

        if ($this->credits->aiEnabled($tenantId) && $this->credits->canUse($tenantId, 1)) {
            try {
                $narrative = $this->llmNarrative($shopData, $result);
                if ($narrative !== null) {
                    $this->credits->consume($tenantId, 1);
                    $result['ai_narrative'] = $narrative;
                    $result['source'] = 'ai';
                }
            } catch (Throwable $e) {
                Log::info('shop_health.ai_failed', ['error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    /** Phân tích quy tắc — điểm 0–100, đánh giá, khuyến nghị. @param array<string,mixed> $d */
    private function rules(array $d): array
    {
        $metrics = array_values(array_filter((array) ($d['metrics'] ?? []), 'is_array'));
        $penalties = array_values(array_filter((array) ($d['penalties'] ?? []), 'is_array'));
        $punishments = array_values(array_filter((array) ($d['punishments'] ?? []), 'is_array'));

        $failed = array_values(array_filter($metrics, fn ($m) => ($m['passed'] ?? null) === false));
        $penaltyPoints = array_sum(array_map(fn ($p) => (int) ($p['points'] ?? 0), $penalties));

        $score = 100;
        $score -= count($failed) * 8;
        $score -= min(40, $penaltyPoints * 3);
        $score -= count($punishments) * 15;
        $score = max(0, min(100, $score));

        $label = match (true) {
            $score >= 85 => 'Tốt',
            $score >= 60 => 'Khá',
            $score >= 40 => 'Cần cải thiện',
            default => 'Rủi ro cao',
        };

        $recommendations = [];
        foreach ($failed as $m) {
            $recommendations[] = [
                'action' => 'Cải thiện chỉ số "'.($m['name'] ?? 'không tên').'"',
                'rationale' => 'Chỉ số đang KHÔNG đạt mục tiêu'
                    .(isset($m['target']) ? ' ('.($m['comparator'] ?? '').' '.$m['target'].')' : '')
                    .' — ưu tiên xử lý để tránh bị hạ hạng/điểm phạt.',
            ];
        }
        if ($penaltyPoints > 0) {
            $top = $penalties[0]['violation_label'] ?? null;
            $recommendations[] = [
                'action' => 'Xử lý nguyên nhân điểm phạt'.($top ? ': '.$top : ''),
                'rationale' => 'Đang có '.$penaltyPoints.' điểm phạt trong quý — điểm phạt cao dẫn tới hạn chế tài khoản.',
            ];
        }
        foreach ($punishments as $p) {
            $recommendations[] = [
                'action' => 'Khắc phục hình phạt: '.($p['type_label'] ?? 'đang áp dụng'),
                'rationale' => 'Hình phạt đang hiệu lực'.(isset($p['tier']) && $p['tier'] ? ' (bậc '.$p['tier'].')' : '').' ảnh hưởng hiển thị/bán hàng.',
            ];
        }
        if ($recommendations === []) {
            $recommendations[] = ['action' => 'Duy trì vận hành tốt', 'rationale' => 'Tất cả chỉ số đạt mục tiêu, không có điểm phạt — tiếp tục giữ phong độ.'];
        }

        $assessment = $failed !== []
            ? 'Có '.count($failed).' chỉ số chưa đạt'.($penaltyPoints > 0 ? ', '.$penaltyPoints.' điểm phạt' : '').($punishments !== [] ? ', '.count($punishments).' hình phạt đang áp dụng' : '').'.'
            : ($penaltyPoints > 0 ? 'Chỉ số ổn nhưng đang có '.$penaltyPoints.' điểm phạt cần xử lý.' : 'Gian hàng đang khỏe mạnh, mọi chỉ số đạt mục tiêu.');

        return [
            'score' => $score,
            'label' => $label,
            'assessment' => $assessment,
            'recommendations' => $recommendations,
            'ai_narrative' => null,
            'source' => 'rule',
        ];
    }

    /**
     * Nhận định ngôn ngữ tự nhiên qua AI provider (Claude). Trả null nếu không có provider/lỗi.
     *
     * @param  array<string,mixed>  $d
     * @param  array<string,mixed>  $base
     */
    private function llmNarrative(array $d, array $base): ?string
    {
        $active = app(AiAssistantRegistry::class)->activeProviders();
        if ($active === []) {
            return null;
        }
        $cfg = app(AiProviderCredentials::class)->resolve($active[0]);
        if ($cfg === null || ! $cfg->apiKey) {
            return null;
        }

        $prompt = 'Bạn là chuyên gia vận hành sàn TMĐT Việt Nam. Dựa trên dữ liệu sức khỏe gian hàng (JSON) và điểm sơ bộ, '
            ."viết 2–4 câu nhận định NGẮN GỌN bằng tiếng Việt: tình trạng chung + rủi ro cần ưu tiên xử lý. CHỈ trả về văn bản thuần (không markdown, không JSON).\n\n"
            ."DỮ LIỆU:\n".json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\nĐiểm sơ bộ: ".$base['score'].'/100 ('.$base['label'].').';

        $res = Http::timeout(20)->withHeaders(['x-api-key' => $cfg->apiKey, 'anthropic-version' => '2023-06-01'])
            ->post(rtrim($cfg->baseUrl ?: 'https://api.anthropic.com', '/').'/v1/messages', [
                'model' => $cfg->defaultModel ?: 'claude-3-5-sonnet-latest',
                'max_tokens' => 400,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

        if (! $res->successful()) {
            return null;
        }
        $text = '';
        foreach ((array) $res->json('content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }
        $text = trim($text);

        return $text !== '' ? $text : null;
    }
}
