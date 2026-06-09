<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsCatalog;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookCampaignBlueprint;
use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use RuntimeException;

/**
 * Sinh một chiến dịch Facebook bằng AI từ ngữ cảnh bài viết + landing + mục tiêu.
 *
 * AI ĐỀ XUẤT số liệu (ngân sách/nhắm mục tiêu/khuyến nghị); SERVER áp đặt phần bắt buộc
 * (mục tiêu, creative theo bài viết, pixel/event, kẹp ngân sách theo guardrail, lịch chạy
 * an toàn) rồi VALIDATE/SANITIZE qua {@see FacebookCampaignBlueprint} trước khi tạo AdDraft.
 * Nhờ vậy dù AI trả sơ sài/lỗi vẫn ra draft hợp lệ (degrade an toàn qua fallback).
 */
final class AiCampaignGenerator
{
    public function __construct(
        private readonly MarketingAnalysisClient $client,
        private readonly AdDraftService $drafts,
        private readonly ScheduleOptimizer $schedule,
        private readonly AdBudgetGuardrails $budget,
    ) {}

    /** @return array{draft: AdDraft, recommendations: list<string>} */
    public function generate(AiCampaignRequest $req): array
    {
        $now = CarbonImmutable::now();

        $ai = $this->client->analyze(
            $this->contextData($req),
            $this->instruction($req),
            json_encode($this->schema(), JSON_THROW_ON_ERROR),
            $this->fallback($req),
            $req->tenantId,
        );
        $aiPayload = $ai['payload'];

        $recommendations = array_values(array_filter(
            array_map(fn ($s) => trim((string) $s), (array) ($aiPayload['recommendations'] ?? [])),
            fn (string $s) => $s !== '',
        ));

        $budgetMode = (($aiPayload['campaign']['budget_mode'] ?? 'adset') === 'campaign') ? 'campaign' : 'adset';

        $start = ($req->startTime !== null && $req->startTime !== '')
            ? CarbonImmutable::parse($req->startTime)
            : $this->schedule->recommendedStart($now, $req->timezone);
        $startIso = $start->setTimezone($req->timezone)->toIso8601String();
        if (($warn = $this->schedule->riskWarning($start, $req->timezone)) !== null) {
            array_unshift($recommendations, $warn);
        }

        $aiAdsets = array_values(array_filter((array) ($aiPayload['adsets'] ?? []), 'is_array')) ?: [[]];
        $adsets = [];
        foreach ($aiAdsets as $idx => $as) {
            $adsets[] = $this->buildAdSet($req, (array) $as, $idx, $budgetMode, $startIso);
        }

        $campaign = ['budget_mode' => $budgetMode];
        if ($budgetMode === 'campaign') {
            $campaign['daily_budget_major'] = $this->budget->clamp((int) ($aiPayload['campaign']['daily_budget_major'] ?? 0), $req->mode);
        }

        $blueprint = FacebookCampaignBlueprint::fromArray(['campaign' => $campaign, 'adsets' => $adsets], $req->objective)->sanitize();
        $errors = $blueprint->validate();
        if ($errors !== []) {
            throw new RuntimeException('Chiến dịch AI tạo chưa hợp lệ: '.implode(' ', $errors));
        }

        $draft = $this->drafts->create($req->adAccountId, $req->userId, [
            'name' => $this->campaignName($req),
            'objective' => $req->objective,
            'payload' => $blueprint->toPayload(),
        ]);

        return ['draft' => $draft, 'recommendations' => $recommendations];
    }

    /**
     * @param  array<string,mixed>  $as
     * @return array<string,mixed>
     */
    private function buildAdSet(AiCampaignRequest $req, array $as, int $idx, string $budgetMode, string $startIso): array
    {
        $targeting = (array) ($as['targeting'] ?? []);
        if (empty($targeting['geo_locations'])) {
            $targeting['geo_locations'] = ['countries' => ['VN']];
        }

        // Advantage+ ⇒ vị trí tự động; thủ công ⇒ dùng cấu hình AI (sẽ được sanitize sau).
        $placement = $req->placementMode === 'advantage_plus'
            ? ['automatic' => true]
            : (array) ($as['placement_config'] ?? ['automatic' => true]);

        $creative = [
            'mode' => 'page_post',
            'page_id' => $req->pageId,
            'page_post_id' => $req->pagePostId,
            'cta' => (string) ($as['ads'][0]['creative']['cta'] ?? $req->ctaType ?? 'LEARN_MORE'),
        ];
        if ($req->linkUrl !== null && $req->linkUrl !== '') {
            $creative['link_url'] = $req->linkUrl;
        }

        $node = [
            'name' => (string) ($as['name'] ?? ('Nhóm '.($idx + 1))),
            'targeting' => $targeting,
            'placement_config' => $placement,
            'schedule' => ['start_time' => $startIso],
            'ads' => [[
                'name' => (string) ($as['ads'][0]['name'] ?? 'Quảng cáo 1'),
                'creative' => $creative,
            ]],
        ];

        if ($budgetMode === 'adset') {
            $node['budget'] = ['daily_major' => $this->budget->clamp((int) ($as['budget']['daily_major'] ?? 0), $req->mode)];
        }

        if ($req->objective === 'conversions') {
            $node['conversion'] = [
                'pixel_id' => (string) $req->pixelId,
                'custom_event_type' => $req->conversionEvent ?? 'COMPLETE_REGISTRATION',
            ];
        }

        return $node;
    }

    private function campaignName(AiCampaignRequest $req): string
    {
        $tag = $req->mode === AdBudgetGuardrails::MODE_SCALE ? 'Scale' : 'Test';

        return "[AI {$tag}] ".CarbonImmutable::now()->setTimezone($req->timezone)->format('d/m H:i');
    }

    /** Schema toàn cây + recommendations để LLM trả structured output. */
    private function schema(): array
    {
        $schema = FacebookAdsCatalog::jsonSchema();
        $schema['properties']['recommendations'] = [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'description' => 'Khuyến nghị thêm sau khi lên chiến dịch (scale tiếp, audience, sáng tạo, cảnh báo).',
        ];

        return $schema;
    }

    /** @return array<string,mixed> */
    private function contextData(AiCampaignRequest $req): array
    {
        return [
            'objective' => $req->objective,
            'mode' => $req->mode,
            'placement_mode' => $req->placementMode,
            'currency' => $req->currency,
            'timezone' => $req->timezone,
            'now_local' => CarbonImmutable::now()->setTimezone($req->timezone)->toIso8601String(),
            'budget_guardrail' => [
                'min' => 50000,
                'recommended' => $this->budget->recommended($req->mode),
                'max' => $this->budget->maxFor($req->mode),
            ],
            'post' => [
                'caption' => $req->caption,
                'likes' => $req->likes,
                'comments' => $req->comments,
                'shares' => $req->shares,
                'link_url' => $req->linkUrl,
                'cta_type' => $req->ctaType,
            ],
            'landing_page_text' => $req->landingText,
            'user_prompt' => $req->prompt,
        ];
    }

    private function instruction(AiCampaignRequest $req): string
    {
        return <<<TXT
        Bạn là CHUYÊN GIA quảng cáo Facebook (Meta) cho thị trường Việt Nam. Nhiệm vụ: từ ngữ
        cảnh bài viết + yêu cầu người dùng, đề xuất CẤU HÌNH CHIẾN DỊCH tối ưu và TRẢ VỀ JSON
        đúng schema (campaign + adsets + recommendations). Đơn vị tiền: {$req->currency} (số nguyên).

        QUY TẮC BẮT BUỘC:
        - Mục tiêu (objective) = giữ nguyên "{$req->objective}". KHÔNG đổi.
        - Chế độ "{$req->mode}": nếu TEST → 1 nhóm, ngân sách NHỎ trong khoảng guardrail để học
          audience/sáng tạo (3–5 ngày), nhắm mục tiêu RỘNG vừa phải. Nếu SCALE → ngân sách LỚN
          hơn, audience đã/đang hiệu quả, có thể nhiều nhóm để test biến thể.
        - Ngân sách/ngày PHẢI nằm trong khoảng budget_guardrail (min..max), ưu tiên gần
          'recommended'. Đừng đề xuất quá nhỏ (FB từ chối) hay quá lớn (đốt tiền).
        - Nhắm mục tiêu: luôn có geo_locations (mặc định {"countries":["VN"]}); chọn age/gender
          hợp sản phẩm dựa trên caption + engagement.
        - Vị trí: nếu placement_mode=advantage_plus → để placement_config.automatic=true. KHÔNG
          BAO GIỜ dùng vị trí đã ngừng hoạt động. Nếu chỉ nhắm mobile thì KHÔNG chọn vị trí
          desktop-only (right_hand_column).
        - Lịch: KHÔNG đặt bắt đầu sát nửa đêm (ngân sách ngày reset lúc 00:00 giờ tài khoản →
          dễ đốt hết trong ít giờ). Dựa vào now_local để chọn giờ bắt đầu hợp lý.
        - Creative: dùng bài viết đã chọn (server sẽ gắn page_id/page_post_id). Với mục tiêu
          chuyển đổi (conversions) cần link đích + sự kiện chuyển đổi (server sẽ gắn pixel/event).

        recommendations: 3–6 gạch đầu dòng NGẮN, hành động được (vd ngưỡng CPM/CPR để scale, gợi ý
        audience/sáng tạo, cảnh báo rủi ro). Chỉ trả JSON, không giải thích ngoài JSON.
        TXT;
    }

    private function fallback(AiCampaignRequest $req): \Closure
    {
        return fn (array $data): array => [
            'campaign' => ['budget_mode' => 'adset'],
            'adsets' => [[
                'name' => 'Nhóm 1',
                'budget' => ['daily_major' => $this->budget->recommended($req->mode)],
                'targeting' => ['geo_locations' => ['countries' => ['VN']]],
                'placement_config' => ['automatic' => true],
                'ads' => [['name' => 'Quảng cáo 1', 'creative' => ['mode' => 'page_post']]],
            ]],
            'recommendations' => ['AI chưa cấu hình hoặc tạm lỗi — đã dùng cấu hình mặc định an toàn. Hãy kiểm tra nhắm mục tiêu & ngân sách trước khi xuất bản.'],
        ];
    }
}
