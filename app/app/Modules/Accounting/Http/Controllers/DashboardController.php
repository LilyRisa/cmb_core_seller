<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\ApService;
use CMBcoreSeller\Modules\Accounting\Services\ArService;
use CMBcoreSeller\Modules\Accounting\Services\CashService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\ProfitLossService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Thống kê nhanh kế toán cho Dashboard. Gộp 5 truy vấn nhỏ (setup, period, cash, AR, AP, P&L kỳ
 * hiện tại) vào 1 endpoint để tránh fan-out request từ trang Bảng điều khiển.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly AccountingSetupService $setup,
        private readonly CashService $cash,
        private readonly ArService $ar,
        private readonly ApService $ap,
        private readonly ProfitLossService $pnl,
    ) {}

    /** GET /accounting/dashboard-summary */
    public function summary(): JsonResponse
    {
        $tenantId = (int) $this->tenant->id();

        if (! $this->setup->isInitialized($tenantId)) {
            return response()->json(['data' => ['initialized' => false]]);
        }

        $period = FiscalPeriod::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('kind', FiscalPeriod::KIND_MONTH)
            ->where('code', now()->format('Y-m'))
            ->first();

        $cashMap = $this->cash->balanceMap($tenantId);
        $cashTotal = array_sum($cashMap);
        $cashAccounts = count($cashMap);

        $arRows = $this->ar->agingByCustomer($tenantId);
        $arTotal = 0; $arOverdue = 0;
        foreach ($arRows as $r) {
            $arTotal += $r['total'];
            $arOverdue += $r['b61_90'] + $r['b90p'];
        }

        $apRows = $this->ap->agingBySupplier($tenantId);
        $apTotal = 0; $apOverdue = 0;
        foreach ($apRows as $r) {
            $apTotal += $r['total'];
            $apOverdue += $r['b61_90'] + $r['b90p'];
        }

        $pl = null;
        if ($period !== null) {
            $r = $this->pnl->generate($tenantId, $period);
            $pl = [
                'revenue' => $r['net_revenue'],
                'cogs' => $r['cogs'],
                'gross_profit' => $r['gross_profit'],
                'opex' => $r['opex'],
                'net_income' => $r['net_income'],
            ];
        }

        return response()->json(['data' => [
            'initialized' => true,
            'current_period' => $period === null ? null : [
                'code' => $period->code,
                'status' => $period->status,
                'status_label' => match ($period->status) {
                    FiscalPeriod::STATUS_OPEN => 'Đang mở',
                    FiscalPeriod::STATUS_CLOSED => 'Đã đóng',
                    FiscalPeriod::STATUS_LOCKED => 'Đã khoá',
                    default => $period->status,
                },
            ],
            'cash' => ['total' => (int) $cashTotal, 'accounts' => $cashAccounts],
            'ar' => ['total' => $arTotal, 'overdue' => $arOverdue],
            'ap' => ['total' => $apTotal, 'overdue' => $apOverdue],
            'pl_period' => $pl,
        ]]);
    }
}
