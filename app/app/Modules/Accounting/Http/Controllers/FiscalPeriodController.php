<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Http\Resources\FiscalPeriodResource;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Services\PeriodService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class FiscalPeriodController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly PeriodService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $q = FiscalPeriod::query()
            ->where('tenant_id', $this->tenant->id())
            ->orderBy('start_date');
        if ($k = $request->query('kind')) {
            $q->where('kind', $k);
        }
        if ($y = $request->query('year')) {
            $q->whereYear('start_date', (int) $y);
        }

        return FiscalPeriodResource::collection($q->get());
    }

    /** POST /accounting/periods/ensure-year — idempotent */
    public function ensureYear(Request $request): JsonResponse
    {
        $request->validate(['year' => 'required|integer|min:2020|max:2099']);
        $count = $this->service->ensureYear((int) $this->tenant->id(), $request->integer('year'));

        return response()->json(['data' => ['created' => $count]]);
    }

    public function close(Request $request, string $code): FiscalPeriodResource
    {
        $request->validate(['note' => 'nullable|string|max:500']);
        $period = $this->find($code);
        $period = $this->service->close($period, (int) $request->user()->getKey(), $request->input('note'));

        return new FiscalPeriodResource($period);
    }

    public function reopen(Request $request, string $code): FiscalPeriodResource
    {
        $period = $this->find($code);
        $period = $this->service->reopen($period, (int) $request->user()->getKey());

        return new FiscalPeriodResource($period);
    }

    public function lock(Request $request, string $code): FiscalPeriodResource
    {
        $period = $this->find($code);
        $period = $this->service->lock($period, (int) $request->user()->getKey());

        return new FiscalPeriodResource($period);
    }

    private function find(string $code): FiscalPeriod
    {
        return FiscalPeriod::query()
            ->where('tenant_id', $this->tenant->id())
            ->where('code', $code)
            ->firstOrFail();
    }
}
