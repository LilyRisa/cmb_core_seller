<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Http\Resources\CashAccountResource;
use CMBcoreSeller\Modules\Accounting\Models\BankStatement;
use CMBcoreSeller\Modules\Accounting\Models\BankStatementLine;
use CMBcoreSeller\Modules\Accounting\Models\CashAccount;
use CMBcoreSeller\Modules\Accounting\Services\BankStatementService;
use CMBcoreSeller\Modules\Accounting\Services\CashService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class CashController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly CashService $cash,
        private readonly BankStatementService $statements,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $tenantId = (int) $this->tenant->id();
        $rows = CashAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('glAccount')->orderBy('code')->get();
        $balances = $this->cash->balanceMap($tenantId);
        foreach ($rows as $r) {
            $r->balance_attr = (int) ($balances[$r->id] ?? 0);
        }

        return CashAccountResource::collection($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:32',
            'name' => 'required|string|max:255',
            'kind' => 'required|in:cash,bank,ewallet,cod_intransit',
            'gl_account_code' => 'required|string|max:16',
            'bank_name' => 'nullable|string|max:100',
            'account_no' => 'nullable|string|max:64',
            'account_holder' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);
        $row = $this->cash->create((int) $this->tenant->id(), $request->only([
            'code', 'name', 'kind', 'gl_account_code', 'bank_name', 'account_no', 'account_holder', 'description',
        ]));

        return (new CashAccountResource($row))->response()->setStatusCode(201);
    }

    /** GET /accounting/bank-statements?cash_account_id= */
    public function listStatements(Request $request): JsonResponse
    {
        $q = BankStatement::query()
            ->where('tenant_id', $this->tenant->id())
            ->orderBy('period_start', 'desc')->orderBy('id', 'desc');
        if ($c = $request->query('cash_account_id')) {
            $q->where('cash_account_id', (int) $c);
        }
        $data = $q->paginate(max(1, min(100, $request->integer('per_page', 20))));

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(), 'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(), 'total' => $data->total(),
            ],
        ]);
    }

    public function showStatement(int $id): JsonResponse
    {
        $stmt = BankStatement::query()->where('tenant_id', $this->tenant->id())->with('lines')->findOrFail($id);

        return response()->json(['data' => array_merge($stmt->toArray(), [
            'lines' => $stmt->lines->toArray(),
        ])]);
    }

    public function importStatement(Request $request): JsonResponse
    {
        $request->validate([
            'cash_account_id' => 'required|integer|min:1',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'imported_from' => 'required|in:csv,mt940,sepay_webhook,manual',
            'lines' => 'required|array|min:1',
            'lines.*.txn_date' => 'required|date',
            'lines.*.amount' => 'required|integer',
            'lines.*.counter_party' => 'nullable|string|max:255',
            'lines.*.memo' => 'nullable|string|max:500',
            'lines.*.external_ref' => 'nullable|string|max:191',
        ]);
        $stmt = $this->statements->import(
            (int) $this->tenant->id(),
            $request->integer('cash_account_id'),
            $request->string('period_start')->toString(),
            $request->string('period_end')->toString(),
            $request->string('imported_from')->toString(),
            $request->input('lines', []),
            (int) $request->user()->getKey(),
        );

        return response()->json(['data' => $stmt])->setStatusCode(201);
    }

    public function matchLine(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'ref_type' => 'required|in:customer_receipt,vendor_payment,journal_entry',
            'ref_id' => 'required|integer|min:1',
            'journal_entry_id' => 'nullable|integer|min:1',
        ]);
        $line = BankStatementLine::query()->where('tenant_id', $this->tenant->id())->findOrFail($id);
        $line = $this->statements->matchLine(
            $line,
            $request->string('ref_type')->toString(),
            $request->integer('ref_id'),
            $request->integer('journal_entry_id') ?: null,
            (int) $request->user()->getKey(),
        );

        return response()->json(['data' => $line]);
    }

    public function ignoreLine(Request $request, int $id): JsonResponse
    {
        $line = BankStatementLine::query()->where('tenant_id', $this->tenant->id())->findOrFail($id);
        $line = $this->statements->ignoreLine($line, (int) $request->user()->getKey());

        return response()->json(['data' => $line]);
    }
}
