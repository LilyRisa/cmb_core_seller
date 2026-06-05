<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Requests\AdMonitorRequest;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdMonitor;
use CMBcoreSeller\Modules\Marketing\Models\AdMonitorAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Per-campaign/adset auto-monitor rules. Read = marketing.view; write =
 * marketing.ads.create. Tenant-scoped via the model global scope.
 */
class AdMonitorController extends Controller
{
    /** GET ad-accounts/{id}/monitors — list (to show which entities are monitored). */
    public function index(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $rows = AdMonitor::query()->where('ad_account_id', $account->getKey())->get()
            ->map(fn (AdMonitor $m) => $this->format($m));

        return response()->json(['data' => $rows]);
    }

    /** PUT ad-accounts/{id}/monitors — create or update the monitor for a target. */
    public function upsert(int $id, AdMonitorRequest $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $account = AdAccount::query()->findOrFail($id);
        $data = $request->validated();

        $monitor = AdMonitor::query()->updateOrCreate(
            [
                'ad_account_id' => $account->getKey(),
                'target_level' => $data['target_level'],
                'target_external_id' => $data['target_external_id'],
            ],
            [
                'tenant_id' => $account->tenant_id,
                'created_by' => $request->user()?->id,
                'enabled' => $data['enabled'] ?? true,
                'increase_enabled' => $data['increase_enabled'] ?? false,
                'increase_below' => $data['increase_below'] ?? null,
                'increase_step_pct' => $data['increase_step_pct'] ?? 20,
                'max_daily_budget' => $data['max_daily_budget'] ?? null,
                'pause_enabled' => $data['pause_enabled'] ?? false,
                'pause_above' => $data['pause_above'] ?? null,
                'min_results' => $data['min_results'] ?? 1,
            ],
        );

        return response()->json(['data' => $this->format($monitor)], $monitor->wasRecentlyCreated ? 201 : 200);
    }

    /** DELETE monitors/{monitor} */
    public function destroy(int $monitor): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        AdMonitor::query()->findOrFail($monitor)->delete();

        return response()->json(null, 204);
    }

    /** GET ad-accounts/{id}/monitor-actions — history of monitor actions (optionally by target). */
    public function actions(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $rows = AdMonitorAction::query()
            ->where('ad_account_id', $account->getKey())
            ->when($request->filled('target_external_id'), fn ($q) => $q->where('target_external_id', $request->string('target_external_id')))
            ->latest('id')->limit(100)->get()
            ->map(fn (AdMonitorAction $a) => [
                'id' => $a->id,
                'target_level' => $a->target_level,
                'target_external_id' => $a->target_external_id,
                'target_name' => $a->target_name,
                'type' => $a->type,
                'cpr' => $a->cpr,
                'spend' => $a->spend,
                'results' => $a->results,
                'from_budget' => $a->from_budget,
                'to_budget' => $a->to_budget,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $rows]);
    }

    /** DELETE monitor-actions/{action} */
    public function destroyAction(int $action): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        AdMonitorAction::query()->findOrFail($action)->delete();

        return response()->json(null, 204);
    }

    /** @return array<string,mixed> */
    private function format(AdMonitor $m): array
    {
        return [
            'id' => $m->id,
            'target_level' => $m->target_level,
            'target_external_id' => $m->target_external_id,
            'enabled' => $m->enabled,
            'increase_enabled' => $m->increase_enabled,
            'increase_below' => $m->increase_below,
            'increase_step_pct' => $m->increase_step_pct,
            'max_daily_budget' => $m->max_daily_budget,
            'pause_enabled' => $m->pause_enabled,
            'pause_above' => $m->pause_above,
            'min_results' => $m->min_results,
            'last_action' => $m->last_action,
            'last_action_at' => $m->last_action_at?->toIso8601String(),
            'last_evaluated_at' => $m->last_evaluated_at?->toIso8601String(),
        ];
    }
}
