<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * AI strategic forecast per ad account. On-demand + cached + cooldown to save
 * AI quota: within the cooldown window the cached forecast is returned WITHOUT
 * calling the AI. Uses the dedicated marketing AI client (isolated).
 */
class AdsForecastService
{
    private const INSTRUCTION = 'Bạn là chuyên gia tối ưu quảng cáo Facebook. Dựa trên đối soát chi tiêu/hội thoại Messenger/leads vs đơn thủ công theo ngày, hãy DỰ BÁO 7 ngày tới và đề xuất CHIẾN LƯỢC tối ưu (tăng/giảm ngân sách, tạm dừng, đổi tệp/nội dung).';

    public function __construct(
        private MarketingAnalysisClient $client,
        private AdReconciliationService $reconciliation,
    ) {}

    public function generate(AdAccount $account, bool $force = false): AdForecast
    {
        $existing = AdForecast::query()->where('ad_account_id', $account->getKey())->first();

        $cooldown = (int) config('marketing.forecast_cooldown_minutes', 360);
        if (! $force && $existing !== null && $existing->generated_at->gt(now()->subMinutes($cooldown))) {
            return $existing; // within cooldown ⇒ cached, NO AI call (quota saved)
        }

        $rows = $this->reconciliation->reconcile($account, 14);
        $result = $this->client->analyze(['rows' => $rows, 'currency' => $account->currency], self::INSTRUCTION);

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
}
