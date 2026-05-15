<?php

namespace CMBcoreSeller\Modules\Accounting\Services\Reports;

use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Accounting\Services\BalanceService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Bảng cân đối số phát sinh. Phase 7.5 — SPEC 0019.
 *
 * Hàng theo TK lá (postable). Trả `opening | debit | credit | closing`.
 */
class TrialBalanceService
{
    public function __construct(private readonly BalanceService $balances) {}

    /**
     * @return array<int, array{account_code:string, account_name:string, type:string, opening:int, debit:int, credit:int, closing:int}>
     */
    public function generate(int $tenantId, FiscalPeriod $period): array
    {
        // Đảm bảo balance đã được recompute cho kỳ này.
        $this->balances->recomputeForPeriod($tenantId, $period);

        $accounts = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('is_postable', true)
            ->orderBy('sort_order')->orderBy('code')->get();

        // Aggregate live cho cả debit & credit của kỳ.
        $sums = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('posted_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->selectRaw('account_id, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->groupBy('account_id')->get()->keyBy('account_id');

        // Tính opening = closing của kỳ trước (cùng kind).
        $prev = FiscalPeriod::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('kind', $period->kind)
            ->where('end_date', '<', $period->start_date)
            ->orderBy('end_date', 'desc')->first();
        $openings = [];
        if ($prev) {
            // closing kỳ trước (live, không qua AccountBalance để chính xác sau recompute).
            $prevSums = JournalLine::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('posted_at', '<', $period->start_date->copy()->startOfDay())
                ->selectRaw('account_id, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
                ->groupBy('account_id')->get()->keyBy('account_id');
            foreach ($prevSums as $accId => $r) {
                $acc = $accounts->where('id', $accId)->first();
                if (! $acc) {
                    continue;
                }
                $openings[$accId] = $acc->isDebitNormal()
                    ? ((int) $r->dr - (int) $r->cr)
                    : ((int) $r->cr - (int) $r->dr);
            }
        }

        $out = [];
        foreach ($accounts as $acc) {
            $s = $sums->get($acc->id);
            $debit = (int) ($s->dr ?? 0);
            $credit = (int) ($s->cr ?? 0);
            $opening = (int) ($openings[$acc->id] ?? 0);
            $closing = $acc->isDebitNormal()
                ? ($opening + $debit - $credit)
                : ($opening + $credit - $debit);
            // Skip hàng toàn 0 (giữ data sạch).
            if ($opening === 0 && $debit === 0 && $credit === 0 && $closing === 0) {
                continue;
            }
            $out[] = [
                'account_code' => $acc->code,
                'account_name' => $acc->name,
                'type' => $acc->type,
                'opening' => $opening,
                'debit' => $debit,
                'credit' => $credit,
                'closing' => $closing,
            ];
        }

        return $out;
    }

    /** Tổng phát sinh và đảm bảo Σ Dr = Σ Cr. */
    public function totals(array $rows): array
    {
        return [
            'total_debit' => array_sum(array_column($rows, 'debit')),
            'total_credit' => array_sum(array_column($rows, 'credit')),
        ];
    }
}
