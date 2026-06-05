<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\CampaignAiInsight;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

/**
 * AI analysis for ONE campaign with caller-chosen window (days), metrics, and
 * (optionally) the campaign's creative content + post engagement. On-demand +
 * cooldown-gated; re-runs immediately when the analysis params change.
 */
class CampaignInsightAnalysisService
{
    /** Metrics the caller may request — keys map to AdInsightDTO properties. */
    public const METRICS = [
        'spend' => 'spend', 'impressions' => 'impressions', 'clicks' => 'clicks', 'reach' => 'reach',
        'ctr' => 'ctr', 'cpc' => 'cpc', 'cpm' => 'cpm', 'frequency' => 'frequency',
        'purchase_roas' => 'purchaseRoas', 'messaging_conversations' => 'messagingConversations', 'leads' => 'leads',
    ];

    private const INSTRUCTION = 'Bạn là chuyên gia tối ưu quảng cáo Facebook. Phân tích RIÊNG một chiến dịch dựa trên: chỉ số đã chọn trong N ngày, hiệu quả từng quảng cáo, nội dung creative/bài viết, tương tác (like/comment), và (nếu có) NỘI DUNG TRANG ĐÍCH (landing_pages: tiêu đề/heading/text/CTA/form/pixel) với chiến dịch chuyển đổi website. Hãy: (1) chấm điểm hiệu quả tổng thể của chiến dịch trên thang 0–100 (score: số nguyên) phản ánh mức độ hiệu quả dựa trên chỉ số, nội dung quảng cáo và trang đích, (2) đánh giá hiệu quả chiến dịch theo các chỉ số đó, (3) nhận xét nội dung & tương tác của từng bài/quảng cáo, (4) nếu có trang đích: đánh giá mức độ khớp giữa quảng cáo và trang đích, trải nghiệm/tốc độ/CTA và việc gắn pixel; (5) đề xuất hành động cụ thể (tăng/giảm ngân sách, tạm dừng, đổi tệp/nội dung, tối ưu trang đích) cho riêng chiến dịch này.';

    /** Output schema the model must follow (matches what CampaignAiInsightDrawer renders). */
    private const SCHEMA = '{score:number (0-100, điểm hiệu quả tổng thể), summary:string (tổng quan ngắn), assessment:string (đánh giá hiệu quả theo chỉ số), recommendations:[{action:string, rationale:string}], creative_review:[{ref:string, name:string, verdict:"tốt"|"cần cải thiện", issues:[string], suggestions:[string]}]}';

    public function __construct(
        private MarketingAnalysisClient $client,
        private AdsRegistry $registry,
        private LandingPageFetcher $landing,
    ) {}

    /**
     * @param  array{days?:int, metrics?:list<string>, include_engagement?:bool, include_landing?:bool}  $params
     */
    public function generate(AdAccount $account, string $campaignExternalId, array $params, bool $force = false): CampaignAiInsight
    {
        $params = $this->normalizeParams($params);
        $existing = $this->cached($account, $campaignExternalId);

        $cooldown = (int) config('marketing.campaign_insight_cooldown_minutes', 60);
        $sameParams = $existing !== null && $existing->params === $params;
        if (! $force && $existing !== null && $sameParams && $existing->generated_at->gt(now()->subMinutes($cooldown))) {
            return $existing; // within cooldown AND same params ⇒ cached, NO AI call
        }

        $data = $this->buildData($account, $campaignExternalId, $params);
        $result = $this->client->analyze($data, self::INSTRUCTION, self::SCHEMA, fn (array $d): array => $this->stub($d));

        // Insert a NEW row each time → full history (the latest is the cached one).
        return CampaignAiInsight::withoutGlobalScope(TenantScope::class)->create([
            'ad_account_id' => (int) $account->getKey(),
            'campaign_external_id' => $campaignExternalId,
            'tenant_id' => (int) $account->tenant_id,
            'payload' => $result['payload'],
            'params' => $params,
            'provider_code' => $result['provider_code'],
            'model' => $result['model'],
            'generated_at' => now(),
        ]);
    }

    public function cached(AdAccount $account, string $campaignExternalId): ?CampaignAiInsight
    {
        return CampaignAiInsight::query()
            ->where('ad_account_id', $account->getKey())
            ->where('campaign_external_id', $campaignExternalId)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array{days?:int, metrics?:list<string>, include_engagement?:bool, include_landing?:bool}  $params
     * @return array{days:int, metrics:list<string>, include_engagement:bool, include_landing:bool}
     */
    public function normalizeParams(array $params): array
    {
        $days = max(1, min(90, (int) ($params['days'] ?? 14)));
        $requested = array_values(array_filter((array) ($params['metrics'] ?? []), 'is_string'));
        $metrics = array_values(array_intersect(array_keys(self::METRICS), $requested));
        if ($metrics === []) {
            $metrics = ['spend', 'impressions', 'clicks', 'ctr', 'cpc', 'purchase_roas'];
        }

        return [
            'days' => $days,
            'metrics' => $metrics,
            'include_engagement' => (bool) ($params['include_engagement'] ?? true),
            'include_landing' => (bool) ($params['include_landing'] ?? true),
        ];
    }

    /**
     * @param  array{days:int, metrics:list<string>, include_engagement:bool, include_landing:bool}  $params
     * @return array<string,mixed>
     */
    private function buildData(AdAccount $account, string $campaignExternalId, array $params): array
    {
        $campaign = AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('level', AdEntity::LEVEL_CAMPAIGN)
            ->where('external_id', $campaignExternalId)
            ->first();

        $since = now()->subDays($params['days'] - 1)->toDateString();
        $until = now()->toDateString();

        $data = [
            'currency' => $account->currency,
            'days' => $params['days'],
            'metrics' => $params['metrics'],
            'campaign' => $campaign === null ? ['external_id' => $campaignExternalId] : [
                'external_id' => $campaign->external_id,
                'name' => $campaign->name,
                'objective' => $campaign->objective,
                'status' => $campaign->effective_status ?? $campaign->status,
                'daily_budget' => $campaign->daily_budget,
                'lifetime_budget' => $campaign->lifetime_budget,
            ],
            'campaign_metrics' => null,
            'ads' => [],
            'creatives' => [],
            'engagement' => [],
            'landing_pages' => [],
        ];

        if (! $this->registry->has($account->provider)) {
            return $data;
        }

        try {
            $connector = $this->registry->for($account->provider);
            $token = (string) $account->access_token;
            $range = ['time_range' => ['since' => $since, 'until' => $until]];

            $campaignRows = $connector->fetchInsights($token, $campaignExternalId, 'campaign', $range);
            $data['campaign_metrics'] = isset($campaignRows[0]) ? $this->pickMetrics($campaignRows[0], $params['metrics']) : null;

            $adRows = $connector->fetchInsights($token, $campaignExternalId, 'ad', $range);
            $adIds = [];
            $data['ads'] = array_map(function (AdInsightDTO $r) use ($params, &$adIds) {
                $adId = isset($r->raw['ad_id']) ? (string) $r->raw['ad_id'] : null;
                if ($adId !== null) {
                    $adIds[$adId] = true;
                }

                return ['ad_id' => $adId] + $this->pickMetrics($r, $params['metrics']);
            }, $adRows);

            if ($connector->supports('creatives.read')) {
                $creatives = array_values(array_filter(
                    $connector->fetchAdCreatives($token, $account->external_account_id),
                    fn ($c) => isset($adIds[$c->adId]),
                ));
                $data['creatives'] = array_map(fn ($c) => [
                    'ad_id' => $c->adId, 'name' => $c->adName, 'primary_text' => $c->primaryText,
                    'headline' => $c->headline, 'cta' => $c->cta, 'post_id' => $c->pagePostId,
                    'link_url' => $c->linkUrl,
                ], $creatives);

                if ($params['include_engagement'] && $connector instanceof AdsWriteConnector) {
                    $postIds = array_values(array_unique(array_filter(array_map(fn ($c) => $c->pagePostId, $creatives))));
                    $data['engagement'] = $connector->fetchPostEngagement($token, $postIds);
                }

                // Website-conversion campaigns: fetch the landing page(s) for richer context.
                if ($params['include_landing']) {
                    $urls = array_values(array_unique(array_filter(array_map(fn ($c) => $c->linkUrl, $creatives))));
                    // Most VN ads are built from an existing page post: the creative carries no
                    // link, the destination lives in the post's call-to-action. Resolve those.
                    if ($connector instanceof AdsWriteConnector) {
                        $postIds = array_values(array_unique(array_filter(array_map(
                            fn ($c) => $c->linkUrl === null ? $c->pagePostId : null,
                            $creatives,
                        ))));
                        if ($postIds !== []) {
                            $urls = array_values(array_unique(array_merge($urls, array_values($connector->fetchPostLinks($token, $postIds)))));
                        }
                    }
                    foreach (array_slice($urls, 0, 3) as $url) { // cap at 3 distinct pages
                        $page = $this->landing->fetch((string) $url);
                        if ($page !== null) {
                            $data['landing_pages'][] = $page;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('marketing.campaign_insight.enrich_failed', ['account' => $account->getKey(), 'campaign' => $campaignExternalId, 'error' => $e->getMessage()]);
        }

        return $data;
    }

    /**
     * @param  list<string>  $metrics
     * @return array<string,mixed>
     */
    private function pickMetrics(AdInsightDTO $r, array $metrics): array
    {
        $all = [
            'spend' => $r->spend, 'impressions' => $r->impressions, 'clicks' => $r->clicks, 'reach' => $r->reach,
            'ctr' => $r->ctr, 'cpc' => $r->cpc, 'cpm' => $r->cpm, 'frequency' => $r->frequency,
            'purchase_roas' => $r->purchaseRoas, 'messaging_conversations' => $r->messagingConversations, 'leads' => $r->leads,
        ];

        return array_intersect_key($all, array_flip($metrics));
    }

    /**
     * Deterministic per-campaign analysis (no AI provider configured / parse fail).
     * Produces the same shape the drawer renders so the feature works out of the box.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function stub(array $data): array
    {
        $campaign = (array) ($data['campaign'] ?? []);
        $name = (string) ($campaign['name'] ?? ($campaign['external_id'] ?? 'chiến dịch'));
        $days = (int) ($data['days'] ?? 0);
        $m = (array) ($data['campaign_metrics'] ?? []);
        $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');

        $parts = [];
        if (isset($m['spend'])) {
            $parts[] = 'chi tiêu '.$fmt($m['spend']).'đ';
        }
        if (isset($m['impressions'])) {
            $parts[] = $fmt($m['impressions']).' hiển thị';
        }
        if (isset($m['clicks'])) {
            $parts[] = $fmt($m['clicks']).' click';
        }
        if (array_key_exists('ctr', $m) && $m['ctr'] !== null) {
            $parts[] = 'CTR '.number_format((float) $m['ctr'], 2, ',', '.').'%';
        }
        $summary = 'Chiến dịch "'.$name.'"'.($days > 0 ? ' trong '.$days.' ngày gần nhất' : '')
            .($parts === [] ? ' chưa có dữ liệu chỉ số trong khoảng này.' : ': '.implode(', ', $parts).'.');

        $recs = [];
        $ctr = array_key_exists('ctr', $m) ? $m['ctr'] : null;
        if ($ctr !== null && (float) $ctr < 1.0) {
            $recs[] = ['action' => 'Cải thiện nội dung/tệp', 'rationale' => 'CTR dưới 1% — thử đổi hình ảnh/tiêu đề hoặc thu hẹp đối tượng.'];
        } elseif ($ctr !== null) {
            $recs[] = ['action' => 'Giữ & cân nhắc tăng ngân sách', 'rationale' => 'CTR ở mức ổn — có thể mở rộng dần ngân sách để tăng kết quả.'];
        }
        if (($m['clicks'] ?? null) === 0) {
            $recs[] = ['action' => 'Xem lại phân phối', 'rationale' => 'Chưa có click — kiểm tra trạng thái, ngân sách và đối tượng.'];
        }
        if ($recs === []) {
            $recs[] = ['action' => 'Tiếp tục theo dõi', 'rationale' => 'Chưa đủ tín hiệu để kết luận; theo dõi thêm vài ngày.'];
        }
        // Landing page hints (website-conversion campaigns).
        $pages = array_values(array_filter((array) ($data['landing_pages'] ?? []), 'is_array'));
        foreach ($pages as $p) {
            if (empty($p['pixels'])) {
                $recs[] = ['action' => 'Gắn Pixel cho trang đích', 'rationale' => 'Trang đích "'.((string) ($p['title'] ?? $p['url'] ?? '')).'" chưa phát hiện pixel theo dõi — khó tối ưu chuyển đổi.'];
            }
            if (empty($p['has_form']) && empty($p['ctas'])) {
                $recs[] = ['action' => 'Bổ sung CTA/biểu mẫu trên trang đích', 'rationale' => 'Trang đích thiếu nút kêu gọi hành động/biểu mẫu rõ ràng.'];
            }
        }

        $creatives = array_values(array_filter((array) ($data['creatives'] ?? []), 'is_array'));
        $engagement = (array) ($data['engagement'] ?? []);
        $review = array_map(function (array $c) use ($engagement) {
            $postId = (string) ($c['post_id'] ?? '');
            $eng = (array) ($engagement[$postId] ?? []);
            $likes = (int) ($eng['likes'] ?? 0);
            $comments = (int) ($eng['comments'] ?? 0);

            return [
                'ref' => (string) ($c['ad_id'] ?? $postId),
                'name' => (string) ($c['name'] ?? ''),
                'verdict' => 'cần xem xét',
                'issues' => [],
                'suggestions' => [
                    'Tương tác bài: '.$likes.' like, '.$comments.' bình luận.',
                    'Bổ sung lời kêu gọi hành động rõ ràng và hình/đoạn mở đầu nổi bật.',
                ],
            ];
        }, $creatives);

        return [
            'score' => $this->stubScore($m, $pages),
            'summary' => $summary,
            'assessment' => $parts === []
                ? 'Chưa đủ dữ liệu để đánh giá hiệu quả trong khoảng thời gian đã chọn.'
                : 'Đánh giá tự động (chưa cấu hình provider AI marketing) dựa trên các chỉ số đã chọn.',
            'recommendations' => $recs,
            'creative_review' => $review,
            'generated_by' => 'stub',
        ];
    }

    /**
     * Deterministic 0–100 effectiveness score from the chosen metrics + landing pages.
     * Baseline 60, nudged by CTR / clicks / ROAS and whether the landing page has a pixel.
     *
     * @param  array<string,mixed>  $m  campaign_metrics
     * @param  array<int,array<string,mixed>>  $pages  landing_pages
     */
    private function stubScore(array $m, array $pages): int
    {
        if ($m === []) {
            return 50; // no metrics selected/available ⇒ neutral.
        }

        $score = 60;
        if (array_key_exists('ctr', $m) && $m['ctr'] !== null) {
            $ctr = (float) $m['ctr'];
            $score += $ctr >= 2 ? 20 : ($ctr >= 1 ? 10 : ($ctr > 0 ? 0 : -20));
        }
        if (array_key_exists('clicks', $m)) {
            $score += ((int) $m['clicks']) > 0 ? 5 : -10;
        }
        if (array_key_exists('purchase_roas', $m) && $m['purchase_roas'] !== null) {
            $roas = (float) $m['purchase_roas'];
            $score += $roas >= 2 ? 15 : ($roas >= 1 ? 5 : 0);
        }
        foreach ($pages as $p) {
            if (! empty($p['pixels'])) {
                $score += 5;
                break;
            }
        }

        return max(0, min(100, $score));
    }
}
