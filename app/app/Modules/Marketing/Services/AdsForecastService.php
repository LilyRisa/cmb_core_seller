<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

/**
 * AI strategic forecast per ad account. On-demand + cached + cooldown to save
 * AI quota: within the cooldown window the cached forecast is returned WITHOUT
 * calling the AI. Uses the dedicated marketing AI client (isolated).
 */
class AdsForecastService
{
    private const INSTRUCTION = 'Bạn là chuyên gia tối ưu quảng cáo Facebook. Dựa trên đối soát chi tiêu/hội thoại Messenger/leads vs đơn thủ công theo ngày, chỉ số theo chiến dịch (HÔM NAY và 14 ngày qua), và NỘI DUNG creative/bài post, hãy: (1) DỰ BÁO 7 ngày tới, (2) đề xuất CHIẾN LƯỢC tối ưu (tăng/giảm ngân sách, tạm dừng, đổi tệp/nội dung), (3) ĐÁNH GIÁ nội dung từng quảng cáo/bài post đã tối ưu chưa.';

    public function __construct(
        private MarketingAnalysisClient $client,
        private AdReconciliationService $reconciliation,
        private AdsRegistry $registry,
    ) {}

    public function generate(AdAccount $account, bool $force = false): AdForecast
    {
        $existing = AdForecast::query()->where('ad_account_id', $account->getKey())->first();

        $cooldown = (int) config('marketing.forecast_cooldown_minutes', 360);
        if (! $force && $existing !== null && $existing->generated_at->gt(now()->subMinutes($cooldown))) {
            return $existing; // within cooldown ⇒ cached, NO AI call (quota saved)
        }

        $rows = $this->reconciliation->reconcile($account, 14);
        $result = $this->client->analyze($this->buildData($account, $rows), self::INSTRUCTION);

        return AdForecast::withoutGlobalScope(TenantScope::class)->updateOrCreate(
            ['ad_account_id' => (int) $account->getKey()],
            [
                'tenant_id' => (int) $account->tenant_id,
                'payload' => $result['payload'],
                'provider_code' => $result['provider_code'],
                'model' => $result['model'],
                'generated_at' => now(),
            ],
        );
    }

    public function cached(AdAccount $account): ?AdForecast
    {
        return AdForecast::query()->where('ad_account_id', $account->getKey())->first();
    }

    /**
     * @param  list<array<string,mixed>>  $reconciliationRows
     * @return array<string,mixed>
     */
    private function buildData(AdAccount $account, array $reconciliationRows): array
    {
        $data = [
            'currency' => $account->currency,
            // Key MUST be 'rows' — the deterministic stub (no-AI path) computes the
            // 7-day forecast from $data['rows'] (LlmMarketingAnalysisClient::stub).
            'rows' => $reconciliationRows,
            'campaigns_today' => [],
            'campaigns_14d' => [],
            'creatives' => [],
        ];

        if (! $this->registry->has($account->provider)) {
            return $data;
        }

        try {
            $connector = $this->registry->for($account->provider);
            $token = (string) $account->access_token;
            $acc = $account->external_account_id;

            $map = fn (array $rows) => array_map(fn ($r) => [
                'campaign_id' => $r->raw['campaign_id'] ?? null,
                'spend' => $r->spend, 'impressions' => $r->impressions, 'clicks' => $r->clicks,
                'ctr' => $r->ctr, 'cpc' => $r->cpc, 'cpm' => $r->cpm, 'roas' => $r->purchaseRoas,
                'conversations' => $r->messagingConversations, 'leads' => $r->leads,
            ], $rows);

            $data['campaigns_today'] = $map($connector->fetchInsights($token, $acc, 'campaign', ['date_preset' => 'today']));
            $data['campaigns_14d'] = $map($connector->fetchInsights($token, $acc, 'campaign', [
                'time_range' => ['since' => now()->subDays(13)->toDateString(), 'until' => now()->toDateString()],
            ]));

            if ($connector->supports('creatives.read')) {
                $data['creatives'] = array_map(fn ($c) => [
                    'ad_id' => $c->adId, 'name' => $c->adName, 'status' => $c->effectiveStatus,
                    'primary_text' => $c->primaryText, 'headline' => $c->headline, 'cta' => $c->cta, 'post_id' => $c->pagePostId,
                ], $connector->fetchAdCreatives($token, $acc));
            }
        } catch (\Throwable $e) {
            Log::warning('marketing.forecast.enrich_failed', ['account' => $account->getKey(), 'error' => $e->getMessage()]);
        }

        return $data;
    }
}
