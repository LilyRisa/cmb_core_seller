<?php

namespace CMBcoreSeller\Modules\Accounting\Services\Reports;

use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Bảng cân đối kế toán (B01-DN nhỏ) — Phase 7.5 — SPEC 0019.
 *
 * Số dư asset/liability/equity tại ngày end_date của period (tích luỹ từ đầu).
 * Trả 3 nhóm: assets / liabilities / equity. Trả phải cân: assets = liab + equity.
 */
class BalanceSheetService
{
    /**
     * @return array{assets:int, liabilities:int, equity:int, balanced:bool, lines:array<int, array{section:string, code:string, name:string, amount:int}>, retained_earnings_net:int}
     */
    public function generate(int $tenantId, FiscalPeriod $period): array
    {
        $accounts = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['asset', 'liability', 'equity', 'contra_asset'])
            ->orderBy('sort_order')->orderBy('code')->get();

        // Tích luỹ Dr-Cr theo TK đến hết end_date.
        $sums = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('posted_at', '<=', $period->end_date->copy()->endOfDay())
            ->selectRaw('account_id, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->groupBy('account_id')->get()->keyBy('account_id');

        $assets = 0; $liab = 0; $equity = 0;
        $lines = [];
        foreach ($accounts as $acc) {
            $s = $sums->get($acc->id);
            $dr = (int) ($s->dr ?? 0);
            $cr = (int) ($s->cr ?? 0);
            $val = $acc->isDebitNormal() ? ($dr - $cr) : ($cr - $dr);
            if ($val === 0) {
                continue;
            }
            if (in_array($acc->type, ['asset'], true)) {
                $assets += $val;
                $section = 'asset';
            } elseif ($acc->type === 'contra_asset') {
                $assets -= abs($val); // hao mòn TSCĐ trừ vào TS
                $section = 'contra_asset';
            } elseif ($acc->type === 'liability') {
                $liab += $val;
                $section = 'liability';
            } elseif ($acc->type === 'equity') {
                $equity += $val;
                $section = 'equity';
            } else {
                continue;
            }
            $lines[] = ['section' => $section, 'code' => $acc->code, 'name' => $acc->name, 'amount' => $val];
        }

        // Lãi/lỗ năm nay (chưa chuyển 911→421) — cộng vào equity để cân:
        // LNST năm nay = (revenue) − (cogs+expense+tax) = closing TK loại 5-8 (signed về equity).
        $pl = $this->retainedEarningsCurrentYear($tenantId, $period);
        $equity += $pl;

        return [
            'assets' => $assets,
            'liabilities' => $liab,
            'equity' => $equity,
            'balanced' => $assets === ($liab + $equity),
            'retained_earnings_net' => $pl,
            'lines' => $lines,
        ];
    }

    private function retainedEarningsCurrentYear(int $tenantId, FiscalPeriod $period): int
    {
        $year = (int) $period->start_date->format('Y');
        $start = $period->start_date->copy()->startOfYear();
        $end = $period->end_date->copy()->endOfDay();

        $accounts = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['revenue', 'cogs', 'expense', 'contra_revenue'])
            ->get();
        $sums = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('posted_at', [$start, $end])
            ->selectRaw('account_id, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->groupBy('account_id')->get()->keyBy('account_id');
        $net = 0;
        foreach ($accounts as $acc) {
            $s = $sums->get($acc->id);
            $dr = (int) ($s->dr ?? 0);
            $cr = (int) ($s->cr ?? 0);
            $val = $acc->isDebitNormal() ? ($dr - $cr) : ($cr - $dr);
            if ($acc->type === 'revenue') {
                $net += $val;
            } elseif (in_array($acc->type, ['cogs', 'expense', 'contra_revenue'], true)) {
                $net -= $val;
            }
        }

        return $net;
    }
}
