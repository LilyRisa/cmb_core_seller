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

    private const INSTRUCTION = 'Bạn là chuyên gia tối ưu quảng cáo Facebook. Phân tích RIÊNG một chiến dịch dựa trên: chỉ số đã chọn trong N ngày, hiệu quả từng quảng cáo, nội dung creative/bài viết và tương tác (like/comment). Hãy: (1) đánh giá hiệu quả chiến dịch theo các chỉ số đó, (2) nhận xét nội dung & tương tác của từng bài/quảng cáo, (3) đề xuất hành động cụ thể (tăng/giảm ngân sách, tạm dừng, đổi tệp/nội dung) cho riêng chiến dịch này.';

    /** Output schema the model must follow (matches what CampaignAiInsightDrawer renders). */
    private const SCHEMA = '{summary:string (tổng quan ngắn), assessment:string (đánh giá hiệu quả theo chỉ số), recommendations:[{action:string, rationale:string}], creative_review:[{ref:string, name:string, verdict:"tốt"|"cần cải thiện", issues:[string], suggestions:[string]}]}';

    public function __construct(
        private MarketingAnalysisClient $client,
        private AdsRegistry $registry,
    ) {}

    /**
     * @param  array{days?:int, metrics?:list<string>, include_engagement?:bool}  $params
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
     * @param  array{days?:int, metrics?:list<string>, include_engagement?:bool}  $params
     * @return array{days:int, metrics:list<string>, include_engagement:bool}
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
        ];
    }

    /**
     * @param  array{days:int, metrics:list<string>, include_engagement:bool}  $params
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
                ], $creatives);

                if ($params['include_engagement'] && $connector instanceof AdsWriteConnector) {
                    $postIds = array_values(array_unique(array_filter(array_map(fn ($c) => $c->pagePostId, $creatives))));
                    $data['engagement'] = $connector->fetchPostEngagement($token, $postIds);
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
            'summary' => $summary,
            'assessment' => $parts === []
                ? 'Chưa đủ dữ liệu để đánh giá hiệu quả trong khoảng thời gian đã chọn.'
                : 'Đánh giá tự động (chưa cấu hình provider AI marketing) dựa trên các chỉ số đã chọn.',
            'recommendations' => $recs,
            'creative_review' => $review,
            'generated_by' => 'stub',
        ];
    }
}
