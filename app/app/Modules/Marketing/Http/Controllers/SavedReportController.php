<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\SavedReport;
use CMBcoreSeller\Modules\Marketing\Services\AdsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Saved report snapshots: capture a report run (level + date range + filters +
 * rows) so it can be reviewed over time. Tenant-scoped; read = marketing.view,
 * write = marketing.ads.create.
 */
class SavedReportController extends Controller
{
    /** GET ad-accounts/{id}/saved-reports — list summaries (no heavy snapshot). */
    public function index(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $rows = SavedReport::query()
            ->where('ad_account_id', $account->getKey())
            ->latest('id')
            ->get()
            ->map(fn (SavedReport $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'level' => $r->level,
                'since' => $r->since->toDateString(),
                'until' => $r->until->toDateString(),
                'filters' => $r->filters,
                'row_count' => count($r->snapshot),
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $rows]);
    }

    /** POST ad-accounts/{id}/saved-reports — capture the current report run. */
    public function store(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $account = AdAccount::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'level' => ['required', Rule::in(['campaign', 'adset', 'ad'])],
            'since' => ['required', 'date'],
            'until' => ['required', 'date'],
            'filters' => ['nullable', 'array'],
        ]);

        $filters = (array) ($validated['filters'] ?? []);
        $rows = app(AdsReportService::class)->report($account, $validated['level'], $validated['since'], $validated['until'], $filters);

        $report = SavedReport::create([
            'tenant_id' => (int) $account->tenant_id,
            'ad_account_id' => (int) $account->getKey(),
            'created_by' => $request->user()?->id,
            'name' => trim((string) ($validated['name'] ?? '')) ?: ('Báo cáo '.$validated['since'].' → '.$validated['until']),
            'level' => $validated['level'],
            'since' => $validated['since'],
            'until' => $validated['until'],
            'currency' => $account->currency,
            'filters' => $filters,
            'snapshot' => $rows,
        ]);

        return response()->json(['data' => ['id' => $report->id, 'name' => $report->name, 'row_count' => count($rows)]], 201);
    }

    /** GET saved-reports/{report} — full snapshot. */
    public function show(int $report): JsonResponse
    {
        Gate::authorize('marketing.view');
        $r = SavedReport::query()->findOrFail($report);

        return response()->json(['data' => [
            'id' => $r->id,
            'name' => $r->name,
            'level' => $r->level,
            'since' => $r->since->toDateString(),
            'until' => $r->until->toDateString(),
            'currency' => $r->currency,
            'filters' => $r->filters,
            'rows' => $r->snapshot,
            'created_at' => $r->created_at?->toIso8601String(),
        ]]);
    }

    /** DELETE saved-reports/{report} */
    public function destroy(int $report): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        SavedReport::query()->findOrFail($report)->delete();

        return response()->json(null, 204);
    }
}
