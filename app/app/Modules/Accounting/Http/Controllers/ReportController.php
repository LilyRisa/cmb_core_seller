<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Services\Reports\BalanceSheetService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\LedgerService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\MisaExportService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\ProfitLossService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\TrialBalanceService;
use CMBcoreSeller\Modules\Accounting\Services\TaxService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class ReportController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly TrialBalanceService $trial,
        private readonly ProfitLossService $pnl,
        private readonly BalanceSheetService $bs,
        private readonly LedgerService $ledger,
        private readonly MisaExportService $misa,
        private readonly TaxService $tax,
    ) {}

    private function period(string $code): FiscalPeriod
    {
        return FiscalPeriod::query()
            ->where('tenant_id', $this->tenant->id())
            ->where('code', $code)->firstOrFail();
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $period = $this->period((string) $request->query('period'));
        $rows = $this->trial->generate((int) $this->tenant->id(), $period);
        $totals = $this->trial->totals($rows);

        return response()->json([
            'data' => $rows,
            'meta' => array_merge($totals, ['period' => $period->code, 'balanced' => $totals['total_debit'] === $totals['total_credit']]),
        ]);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $period = $this->period((string) $request->query('period'));
        $result = $this->pnl->generate((int) $this->tenant->id(), $period);

        return response()->json([
            'data' => $result,
            'meta' => ['period' => $period->code],
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $period = $this->period((string) $request->query('period'));
        $result = $this->bs->generate((int) $this->tenant->id(), $period);

        return response()->json([
            'data' => $result,
            'meta' => ['period' => $period->code, 'as_of' => $period->end_date->toDateString()],
        ]);
    }

    public function ledger(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|string',
            'account_code' => 'required|string|max:16',
        ]);
        $period = $this->period($request->string('period')->toString());
        $result = $this->ledger->generate((int) $this->tenant->id(), $request->string('account_code')->toString(), $period);

        return response()->json([
            'data' => $result,
            'meta' => ['period' => $period->code],
        ]);
    }

    /** GET /accounting/reports/vat?period=YYYY-MM */
    public function vat(Request $request): JsonResponse
    {
        $period = $this->period((string) $request->query('period'));
        $agg = $this->tax->aggregate((int) $this->tenant->id(), $period);

        return response()->json([
            'data' => $agg,
            'meta' => ['period' => $period->code],
        ]);
    }

    public function createFiling(Request $request): JsonResponse
    {
        $request->validate(['period' => 'required|string']);
        $period = $this->period($request->string('period')->toString());
        $filing = $this->tax->generateFiling((int) $this->tenant->id(), $period);

        return response()->json(['data' => $filing]);
    }

    /** GET /accounting/reports/export-misa?period=YYYY-MM */
    public function exportMisa(Request $request): Response
    {
        $period = $this->period((string) $request->query('period'));
        $files = $this->misa->generate((int) $this->tenant->id(), $period);

        if (count($files) === 1) {
            $name = array_key_first($files);
            return response($files[$name], 200)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="'.$name.'"');
        }
        // Gói thành ZIP cho UX gọn.
        $tmp = tempnam(sys_get_temp_dir(), 'misa').'.zip';
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $data = file_get_contents($tmp);
        @unlink($tmp);

        return response($data, 200)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="accounting-export-misa-'.$period->code.'.zip"');
    }
}
