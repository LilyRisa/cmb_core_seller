<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Http\Resources\AccountBalanceResource;
use CMBcoreSeller\Modules\Accounting\Models\AccountBalance;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Services\BalanceService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class BalanceController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly BalanceService $service,
    ) {}

    /** GET /accounting/balances?period=YYYY-MM */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $periodCode = (string) $request->query('period', now()->format('Y-m'));
        $period = FiscalPeriod::query()
            ->where('tenant_id', $this->tenant->id())
            ->where('code', $periodCode)->first();
        if ($period === null) {
            return response()->json(['data' => []]);
        }

        $q = AccountBalance::query()
            ->where('tenant_id', $this->tenant->id())
            ->where('period_id', $period->id)
            ->whereNull('party_type')->whereNull('party_id')
            ->whereNull('dim_warehouse_id')->whereNull('dim_shop_id')
            ->with(['account', 'period'])
            ->orderBy('account_id');

        return AccountBalanceResource::collection($q->get());
    }

    /** POST /accounting/balances/recompute */
    public function recompute(Request $request): JsonResponse
    {
        $request->validate(['period' => 'required|string|max:16']);
        $period = FiscalPeriod::query()
            ->where('tenant_id', $this->tenant->id())
            ->where('code', $request->string('period'))->firstOrFail();
        $count = $this->service->recomputeForPeriod((int) $this->tenant->id(), $period);

        return response()->json(['data' => ['rows' => $count, 'period' => $period->code]]);
    }
}
