<?php

namespace CMBcoreSeller\Modules\Accounting\Services\Reports;

use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Sổ chi tiết tài khoản — running balance. Phase 7.5 — SPEC 0019.
 */
class LedgerService
{
    /**
     * @return array{account_code:string, account_name:string, opening:int, lines:array<int, array{posted_at:string, entry_code:string, narration:string|null, dr:int, cr:int, running:int}>, total_debit:int, total_credit:int, closing:int}
     */
    public function generate(int $tenantId, string $accountCode, FiscalPeriod $period): array
    {
        $acc = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('code', $accountCode)->first();
        if (! $acc) {
            return [
                'account_code' => $accountCode,
                'account_name' => '',
                'opening' => 0,
                'lines' => [],
                'total_debit' => 0,
                'total_credit' => 0,
                'closing' => 0,
            ];
        }

        // Opening = tích luỹ trước period start.
        $prev = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_id', $acc->id)
            ->where('posted_at', '<', $period->start_date->copy()->startOfDay())
            ->selectRaw('SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->first();
        $opening = $acc->isDebitNormal()
            ? ((int) ($prev->dr ?? 0) - (int) ($prev->cr ?? 0))
            : ((int) ($prev->cr ?? 0) - (int) ($prev->dr ?? 0));

        $lines = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_id', $acc->id)
            ->whereBetween('posted_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->orderBy('posted_at')->orderBy('id')
            ->get();

        // Load entry codes một lần.
        $entryIds = $lines->pluck('entry_id')->unique()->values()->all();
        $entries = $entryIds
            ? JournalEntry::query()->withoutGlobalScope(TenantScope::class)
                ->whereIn('id', $entryIds)->get(['id', 'code', 'narration'])->keyBy('id')
            : collect();

        $running = $opening;
        $totalDr = 0;
        $totalCr = 0;
        $rows = [];
        foreach ($lines as $l) {
            $dr = (int) $l->dr_amount;
            $cr = (int) $l->cr_amount;
            $running = $acc->isDebitNormal()
                ? ($running + $dr - $cr)
                : ($running + $cr - $dr);
            $totalDr += $dr;
            $totalCr += $cr;
            $entry = $entries->get($l->entry_id);
            $rows[] = [
                'posted_at' => $l->posted_at->toIso8601String(),
                'entry_code' => $entry?->code ?? '',
                'narration' => $entry?->narration ?? $l->memo,
                'dr' => $dr,
                'cr' => $cr,
                'running' => $running,
            ];
        }

        return [
            'account_code' => $acc->code,
            'account_name' => $acc->name,
            'opening' => $opening,
            'lines' => $rows,
            'total_debit' => $totalDr,
            'total_credit' => $totalCr,
            'closing' => $running,
        ];
    }
}
