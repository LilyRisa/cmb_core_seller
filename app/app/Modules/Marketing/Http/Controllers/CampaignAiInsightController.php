<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Requests\CampaignAiInsightRequest;
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateCampaignAiInsight;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\CampaignAiInsight;
use CMBcoreSeller\Modules\Marketing\Services\CampaignInsightAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Per-campaign AI analysis. POST generates async (cooldown-guarded — within the
 * window AND same params it returns the cache WITHOUT calling AI). GET reads cache.
 */
class CampaignAiInsightController extends Controller
{
    public function __construct(private CampaignInsightAnalysisService $service) {}

    /** GET /api/v1/marketing/ad-accounts/{id}/campaigns/{campaignId}/ai-insight */
    public function show(int $id, string $campaignId): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        return response()->json(['data' => $this->format($this->service->cached($account, $campaignId))]);
    }

    /** GET .../ai-insight/history — past analyses for this campaign. */
    public function history(int $id, string $campaignId): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $rows = CampaignAiInsight::query()
            ->where('ad_account_id', $account->getKey())
            ->where('campaign_external_id', $campaignId)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (CampaignAiInsight $i) => $this->format($i));

        return response()->json(['data' => $rows]);
    }

    /** DELETE .../campaign-insights/{insight} — delete one history entry. */
    public function destroy(int $insight): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        CampaignAiInsight::query()->findOrFail($insight)->delete();

        return response()->json(null, 204);
    }

    /** POST /api/v1/marketing/ad-accounts/{id}/campaigns/{campaignId}/ai-insight */
    public function generate(int $id, string $campaignId, CampaignAiInsightRequest $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $params = $this->service->normalizeParams($request->params());
        $existing = $this->service->cached($account, $campaignId);
        $cooldown = (int) config('marketing.campaign_insight_cooldown_minutes', 60);
        if ($existing !== null && $existing->params === $params && $existing->generated_at->gt(now()->subMinutes($cooldown))) {
            return response()->json(['data' => $this->format($existing), 'status' => 'cached', 'queued' => false]);
        }

        GenerateCampaignAiInsight::dispatch($account->id, $campaignId, $params);

        return response()->json(['data' => $this->format($existing), 'status' => 'generating', 'queued' => true]);
    }

    /** @return array<string,mixed>|null */
    private function format(?CampaignAiInsight $i): ?array
    {
        if ($i === null) {
            return null;
        }

        return [
            'id' => $i->id,
            'payload' => $i->payload,
            'params' => $i->params,
            'provider_code' => $i->provider_code,
            'model' => $i->model,
            'generated_at' => $i->generated_at->toIso8601String(),
        ];
    }
}
