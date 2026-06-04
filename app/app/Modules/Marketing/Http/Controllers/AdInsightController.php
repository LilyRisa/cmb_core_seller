<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Marketing\Services\AdReconciliationService;
use CMBcoreSeller\Modules\Marketing\Services\AdsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard data for one ad account: account summary + entity tree (campaign/
 * adset/ad), each with its latest insight snapshot for the requested window.
 * Tenant-scoped via global scope; tokens never exposed.
 */
class AdInsightController extends Controller
{
    /** GET /api/v1/marketing/ad-accounts/{id}/insights?window=today */
    public function index(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');

        $account = AdAccount::query()->findOrFail($id);
        $window = (string) (request('window', 'today'));

        // Latest snapshot per external_id for this account+window.
        $snaps = AdInsightSnapshot::query()
            ->where('ad_account_id', $account->getKey())
            ->where('window', $window)
            ->orderByDesc('fetched_at')
            ->get()
            ->keyBy('external_id');

        $entities = AdEntity::query()
            ->where('ad_account_id', $account->getKey())
            ->orderBy('level')->orderBy('id')
            ->get()
            ->map(fn (AdEntity $e) => [
                'id' => $e->id,
                'level' => $e->level,
                'external_id' => $e->external_id,
                'parent_id' => $e->parent_id,
                'name' => $e->name,
                'status' => $e->status,
                'effective_status' => $e->effective_status,
                'daily_budget' => $e->daily_budget,
                'lifetime_budget' => $e->lifetime_budget,
                'insights' => $this->formatSnapshot($snaps->get($e->external_id)),
            ])->values();

        return response()->json([
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency' => $account->currency,
                    'status' => $account->status,
                    'insights_synced_at' => $account->insights_synced_at?->toIso8601String(),
                    'insights' => $this->formatSnapshot($snaps->get($account->external_account_id)),
                ],
                'entities' => $entities,
            ],
        ]);
    }

    /**
     * GET /api/v1/marketing/ad-accounts/{id}/report
     * ?level=campaign|adset|ad&since=&until=&campaign_ids[]=&adset_ids[]=&q=&objective=&ad_id=
     */
    public function report(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $level = in_array(request('level'), ['campaign', 'adset', 'ad'], true) ? (string) request('level') : 'campaign';
        $until = (string) (request('until') ?: now()->toDateString());
        $since = (string) (request('since') ?: now()->subDays(6)->toDateString());
        $filters = [
            'campaign_ids' => array_values(array_filter((array) request('campaign_ids', []))),
            'adset_ids' => array_values(array_filter((array) request('adset_ids', []))),
            'q' => (string) request('q', ''),
            'objective' => (string) request('objective', ''),
            'id' => (string) request('ad_id', ''),
        ];

        return response()->json([
            'data' => [
                'level' => $level,
                'currency' => $account->currency,
                'rows' => app(AdsReportService::class)->report($account, $level, $since, $until, $filters),
            ],
        ]);
    }

    /** GET /api/v1/marketing/ad-accounts/{id}/reconciliation?days=14 */
    public function reconciliation(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);
        $days = max(1, min(90, (int) request('days', 14)));

        return response()->json([
            'data' => [
                'currency' => $account->currency,
                'rows' => app(AdReconciliationService::class)->reconcile($account, $days),
            ],
        ]);
    }

    /** @return array<string,mixed>|null */
    private function formatSnapshot(?AdInsightSnapshot $s): ?array
    {
        if ($s === null) {
            return null;
        }

        return [
            'window' => $s->window,
            'date_start' => $s->date_start,
            'date_stop' => $s->date_stop,
            'is_finalizing' => (bool) $s->is_finalizing,
            'spend' => (int) $s->spend,
            'impressions' => (int) $s->impressions,
            'clicks' => (int) $s->clicks,
            'reach' => (int) $s->reach,
            'ctr' => $s->ctr !== null ? (float) $s->ctr : null,
            'cpc' => $s->cpc !== null ? (int) $s->cpc : null,
            'cpm' => $s->cpm !== null ? (int) $s->cpm : null,
            'frequency' => $s->frequency !== null ? (float) $s->frequency : null,
            'purchase_roas' => $s->purchase_roas !== null ? (float) $s->purchase_roas : null,
            'fetched_at' => $s->fetched_at?->toIso8601String(),
        ];
    }
}
