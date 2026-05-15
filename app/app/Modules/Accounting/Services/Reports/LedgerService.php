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
    /** Tránh OOM khi TK trung tâm có hàng triệu dòng/kỳ. */
    public const HARD_LIMIT = 5000;

    /**
     * @return array{account_code:string, account_name:string, opening:int, lines:array<int, array{posted_at:string, entry_code:string, narration:string|null, dr:int, cr:int, running:int}>, total_debit:int, total_credit:int, closing:int, truncated:bool, total_lines:int}
     */
    public function generate(int $tenantId, string $accountCode, FiscalPeriod $period, int $limit = 0, int $offset = 0): array
    {
        if ($limit <= 0 || $limit > self::HARD_LIMIT) {
            $limit = self::HARD_LIMIT;
        }
        $offset = max(0, $offset);

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
                'truncated' => false,
                'total_lines' => 0,
            ];
        }

        // Opening = tích luỹ trước period start.
        $prev = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_id', $acc->id)
            ->where('posted_at', '<', $period->start_date)
            ->selectRaw('SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->first();
        $opening = $acc->isDebitNormal()
            ? ((int) ($prev->dr ?? 0) - (int) ($prev->cr ?? 0))
            : ((int) ($prev->cr ?? 0) - (int) ($prev->dr ?? 0));

        // Audit-fix: aggregate totals + count ở SQL (tránh load all lines vào memory chỉ để đếm).
        $agg = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_id', $acc->id)
            ->whereBetween('posted_at', [$period->start_date, $period->end_date->copy()->endOfDay()])
            ->selectRaw('COALESCE(SUM(dr_amount),0) as dr, COALESCE(SUM(cr_amount),0) as cr, COUNT(*) as cnt')
            ->first();
        $totalDr = (int) ($agg->dr ?? 0);
        $totalCr = (int) ($agg->cr ?? 0);
        $totalLines = (int) ($agg->cnt ?? 0);

        // Cap lines load — chống OOM với TK trung tâm 100k+ dòng.
        $lines = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_id', $acc->id)
            ->whereBetween('posted_at', [$period->start_date, $period->end_date->copy()->endOfDay()])
            ->orderBy('posted_at')->orderBy('id')
            ->offset($offset)->limit($limit)
            ->get();

        $entryIds = $lines->pluck('entry_id')->unique()->values()->all();
        $entries = $entryIds
            ? JournalEntry::query()->withoutGlobalScope(TenantScope::class)
                ->whereIn('id', $entryIds)->get(['id', 'code', 'narration'])->keyBy('id')
            : collect();

        // Running balance: bắt đầu từ opening + cộng dồn các line trước offset (lookup SUM lại).
        if ($offset > 0) {
            $skipped = JournalLine::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('account_id', $acc->id)
                ->whereBetween('posted_at', [$period->start_date, $period->end_date->copy()->endOfDay()])
                ->orderBy('posted_at')->orderBy('id')
                ->limit($offset)
                ->selectRaw('SUM(dr_amount) as dr, SUM(cr_amount) as cr')->first();
            $running = $opening + ($acc->isDebitNormal()
                ? ((int) ($skipped->dr ?? 0) - (int) ($skipped->cr ?? 0))
                : ((int) ($skipped->cr ?? 0) - (int) ($skipped->dr ?? 0)));
        } else {
            $running = $opening;
        }

        $rows = [];
        foreach ($lines as $l) {
            $dr = (int) $l->dr_amount;
            $cr = (int) $l->cr_amount;
            $running = $acc->isDebitNormal()
                ? ($running + $dr - $cr)
                : ($running + $cr - $dr);
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

        // closing = opening + totals (đúng cho cả khi truncate vì tính từ aggregate, không từ rows).
        $closing = $acc->isDebitNormal()
            ? ($opening + $totalDr - $totalCr)
            : ($opening + $totalCr - $totalDr);

        return [
            'account_code' => $acc->code,
            'account_name' => $acc->name,
            'opening' => $opening,
            'lines' => $rows,
            'total_debit' => $totalDr,
            'total_credit' => $totalCr,
            'closing' => $closing,
            'truncated' => $totalLines > ($offset + count($rows)),
            'total_lines' => $totalLines,
        ];
    }
}
