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
     * Aging buckets (0-30, 31-60, 61-90, >90 ngày). Tổng `balance` >= 0.
     *
     * @return array<int, array{customer_id:int, total:int, b0_30:int, b31_60:int, b61_90:int, b90p:int}>
     */
    public function agingByCustomer(int $tenantId, ?Carbon $asOf = null): array
    {
        $asOf ??= Carbon::now();
        $rows = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('party_type', 'customer')
            ->where('account_code', '131')
            ->where('posted_at', '<=', $asOf)
            ->selectRaw('party_id as customer_id, posted_at, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->groupBy('party_id', 'posted_at')
            ->get();

        $bucketsByCustomer = [];
        foreach ($rows as $r) {
            $cid = (int) ($r->customer_id ?? 0);
            if ($cid === 0) {
                continue;
            }
            $amount = (int) $r->dr - (int) $r->cr;
            if ($amount === 0) {
                continue;
            }
            $daysAgo = Carbon::parse($r->posted_at)->diffInDays($asOf);
            $bucket = match (true) {
                $daysAgo <= 30 => 'b0_30',
                $daysAgo <= 60 => 'b31_60',
                $daysAgo <= 90 => 'b61_90',
                default => 'b90p',
            };
            $bucketsByCustomer[$cid] ??= ['customer_id' => $cid, 'total' => 0, 'b0_30' => 0, 'b31_60' => 0, 'b61_90' => 0, 'b90p' => 0];
            $bucketsByCustomer[$cid][$bucket] += $amount;
            $bucketsByCustomer[$cid]['total'] += $amount;
        }
        // Bỏ khách có balance 0 (đã thu hết).
        return array_values(array_filter($bucketsByCustomer, fn ($b) => $b['total'] > 0));
    }
}
