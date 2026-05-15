<?php

namespace CMBcoreSeller\Modules\Accounting\Services\Reports;

use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Báo cáo Kết quả KD (B02-DN nhỏ) theo TT133. Phase 7.5 — SPEC 0019.
 *
 * Cấu trúc B02 rút gọn:
 *   1. Doanh thu bán hàng & CCDV (511, 515)
 *   2. Các khoản giảm trừ DT (521)
 *   3. Doanh thu thuần (= 1 - 2)
 *   4. Giá vốn hàng bán (632)
 *   5. Lợi nhuận gộp (= 3 - 4)
 *   6. Doanh thu hoạt động tài chính (515 — đã ở 1 — bỏ; còn 711 thu nhập khác)
 *   7. Chi phí tài chính (635)
 *   8. Chi phí QLKD (642 / 6421+6422)
 *   9. Lợi nhuận thuần (= 5 - 7 - 8 + 711)
 *  10. Chi phí khác (811)
 *  11. LN trước thuế (= 9 - 10)
 *  12. Thuế TNDN (821)
 *  13. LN sau thuế (= 11 - 12)
 */
class ProfitLossService
{
    /**
     * @return array{revenue:int, deductions:int, net_revenue:int, cogs:int, gross_profit:int, fin_income:int, fin_expense:int, opex:int, other_income:int, other_expense:int, ebit:int, ebt:int, tax:int, net_income:int, lines:array<int, array{section:string, code:string, name:string, amount:int}>}
     */
    public function generate(int $tenantId, FiscalPeriod $period): array
    {
        $accounts = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['revenue', 'cogs', 'expense', 'contra_revenue'])
            ->get();

        $sums = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('posted_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->selectRaw('account_id, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->groupBy('account_id')->get()->keyBy('account_id');

        $rev = 0; $deductions = 0; $cogs = 0; $opex = 0; $finExp = 0; $finInc = 0;
        $otherInc = 0; $otherExp = 0; $tax = 0;
        $lines = [];

        foreach ($accounts as $acc) {
            $s = $sums->get($acc->id);
            $dr = (int) ($s->dr ?? 0);
            $cr = (int) ($s->cr ?? 0);
            // Revenue (511, 5111, 5113): cr - dr
            // Expense (632, 642, 635, 811, 821): dr - cr
            // Contra revenue (521): dr - cr (trừ vào revenue)
            $val = $acc->isDebitNormal() ? ($dr - $cr) : ($cr - $dr);
            if ($val === 0) {
                continue;
            }
            // Phân nhóm theo prefix code
            $section = 'other';
            if (str_starts_with($acc->code, '511')) { $rev += $val; $section = 'revenue'; }
            elseif ($acc->code === '515') { $finInc += $val; $section = 'fin_income'; }
            elseif (str_starts_with($acc->code, '521')) { $deductions += $val; $section = 'deductions'; }
            elseif ($acc->code === '632') { $cogs += $val; $section = 'cogs'; }
            elseif ($acc->code === '635') { $finExp += $val; $section = 'fin_expense'; }
            elseif (str_starts_with($acc->code, '642')) { $opex += $val; $section = 'opex'; }
            elseif ($acc->code === '711') { $otherInc += $val; $section = 'other_income'; }
            elseif ($acc->code === '811') { $otherExp += $val; $section = 'other_expense'; }
            elseif ($acc->code === '821') { $tax += $val; $section = 'tax'; }
            elseif (str_starts_with($acc->code, '5')) { $rev += $val; $section = 'revenue'; }
            elseif (str_starts_with($acc->code, '6') || str_starts_with($acc->code, '8')) { $opex += $val; $section = 'opex'; }
            $lines[] = [
                'section' => $section,
                'code' => $acc->code,
                'name' => $acc->name,
                'amount' => $val,
            ];
        }

        $netRevenue = $rev - $deductions;
        $grossProfit = $netRevenue - $cogs;
        $ebit = $grossProfit + $finInc + $otherInc - $finExp - $opex;
        $ebt = $ebit - $otherExp;
        $netIncome = $ebt - $tax;

        return [
            'revenue' => $rev,
            'deductions' => $deductions,
            'net_revenue' => $netRevenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'fin_income' => $finInc,
            'fin_expense' => $finExp,
            'opex' => $opex,
            'other_income' => $otherInc,
            'other_expense' => $otherExp,
            'ebit' => $ebit,
            'ebt' => $ebt,
            'tax' => $tax,
            'net_income' => $netIncome,
            'lines' => $lines,
        ];
    }
}
