<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AR (Accounts Receivable) — sổ chi tiết 131 theo khách hàng. Phase 7.2 — SPEC 0019.
 *
 * Cách tính: aggregate `journal_lines.party_type='customer'` theo (customer_id, account_code='131').
 *  - Số dư = SUM(dr) − SUM(cr) (TK 131 debit-normal). Dương = khách còn nợ shop.
 *  - Aging: chia bucket theo `posted_at` của line trừ chưa thu.
 */
class ArService
{
    /**
     * Tổng phải thu (131) per customer cho tenant.
     *
     * @return array<int, array{customer_id:int, balance:int, debit:int, credit:int}>
     */
    public function balancesByCustomer(int $tenantId, ?int $customerId = null): array
    {
        $q = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('party_type', 'customer')
            ->where('account_code', '131');
        if ($customerId !== null) {
            $q->where('party_id', $customerId);
        }
        $rows = $q->selectRaw('party_id as customer_id, SUM(dr_amount) as debit, SUM(cr_amount) as credit')
            ->groupBy('party_id')->get();

        $out = [];
        foreach ($rows as $r) {
            $cid = (int) ($r->customer_id ?? 0);
            if ($cid === 0) {
                continue;
            }
            $out[$cid] = [
                'customer_id' => $cid,
                'debit' => (int) $r->debit,
                'credit' => (int) $r->credit,
                'balance' => (int) $r->debit - (int) $r->credit,
            ];
        }

        return $out;
    }

    /**
     * Aging buckets (0-30, 31-60, 61-90, >90 ngày). Tổng `balance` > 0.
     *
     * Audit-fix: aggregate buckets ngay tại SQL (`SUM(CASE WHEN...)`) — thay vì group by
     * (party_id, posted_at) rồi loop PHP O(khách × ngày). Lợi 100× khi shop có khách lâu năm.
     *
     * @return array<int, array{customer_id:int, total:int, b0_30:int, b31_60:int, b61_90:int, b90p:int}>
     */
    public function agingByCustomer(int $tenantId, ?Carbon $asOf = null): array
    {
        $asOf ??= Carbon::now();
        $asOfStr = $asOf->toDateTimeString();
        $daysExpr = $this->daysDiffExpr();

        $rows = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('party_type', 'customer')
            ->where('account_code', '131')
            ->where('posted_at', '<=', $asOf)
            ->whereNotNull('party_id')
            ->selectRaw("party_id as customer_id,
                SUM(CASE WHEN {$daysExpr} <= 30 THEN dr_amount - cr_amount ELSE 0 END) as b0_30,
                SUM(CASE WHEN {$daysExpr} > 30 AND {$daysExpr} <= 60 THEN dr_amount - cr_amount ELSE 0 END) as b31_60,
                SUM(CASE WHEN {$daysExpr} > 60 AND {$daysExpr} <= 90 THEN dr_amount - cr_amount ELSE 0 END) as b61_90,
                SUM(CASE WHEN {$daysExpr} > 90 THEN dr_amount - cr_amount ELSE 0 END) as b90p,
                SUM(dr_amount - cr_amount) as total",
                array_fill(0, 6, $asOfStr)) // 6 lần `?` trong CASE WHEN (1+2+2+1)
            ->groupBy('party_id')
            ->havingRaw('SUM(dr_amount - cr_amount) > 0')
            ->get();

        return $rows->map(fn ($r) => [
            'customer_id' => (int) $r->customer_id,
            'total' => (int) $r->total,
            'b0_30' => (int) $r->b0_30,
            'b31_60' => (int) $r->b31_60,
            'b61_90' => (int) $r->b61_90,
            'b90p' => (int) $r->b90p,
        ])->all();
    }

    /** Expression SQL trả về số ngày giữa `?` và `posted_at`. Portable Postgres/SQLite/MySQL. */
    private function daysDiffExpr(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => 'CAST(julianday(?) - julianday(posted_at) AS INTEGER)',
            'pgsql' => "EXTRACT(DAY FROM (?::timestamp - posted_at))",
            default => 'TIMESTAMPDIFF(DAY, posted_at, ?)',
        };
    }
}
