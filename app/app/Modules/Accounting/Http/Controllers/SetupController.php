<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SetupController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly AccountingSetupService $setup,
    ) {}

    /** GET /accounting/setup/status */
    public function status(): JsonResponse
    {
        $tenantId = (int) $this->tenant->id();

        return response()->json(['data' => [
            'initialized' => $this->setup->isInitialized($tenantId),
        ]]);
    }

    /** POST /accounting/setup — idempotent */
    public function run(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2099',
        ]);
        $tenantId = (int) $this->tenant->id();
        $result = $this->setup->run($tenantId, $request->integer('year') ?: null);

        return response()->json(['data' => $result]);
    }
}
