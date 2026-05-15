<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Models\AccountBalance;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

/**
 * Recompute number-from-scratch số dư tài khoản theo kỳ.
 * Phase 7.1 — SPEC 0019. Báo cáo BCTC Phase 7.5 đọc từ đây thay vì aggregate live.
 *
 * Cách tính:
 *   - debit, credit của kỳ = SUM journal_lines theo (account_id, period_id).
 *   - opening = closing của kỳ trước (cùng account_id). Kỳ đầu tiên (chưa có previous) = 0.
 *   - closing tuỳ normal_balance: debit-normal ⇒ opening + debit - credit; credit-normal ⇒ opening + credit - debit.
 *
 * Chỉ recompute mức tổng (party/dim = NULL) ở SPEC này. Mức chi tiết party_*
 * sẽ được aggregate query trực tiếp ở Phase 7.2 (AR) / 7.3 (AP) — không cần materialize.
 */
class BalanceService
{
    public function recomputeForPeriod(int $tenantId, FiscalPeriod $period): int
    {
        return DB::transaction(function () use ($tenantId, $period) {
            // Tổng phát sinh theo TK trong kỳ.
            $sums = JournalLine::query()
                ->withoutGlobalScope(TenantScope::class)
                ->selectRaw('account_id, account_code, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
                ->where('tenant_id', $tenantId)
                ->whereBetween('posted_at', [
                    $period->start_date->copy()->startOfDay(),
                    $period->end_date->copy()->endOfDay(),
                ])
                ->groupBy('account_id', 'account_code')
                ->get();

            // Map TK → normal_balance
            $accounts = ChartAccount::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->get()->keyBy('id');

            // Wipe summary rows cho period này (party/dim NULL) — rebuild full.
            AccountBalance::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('period_id', $period->id)
                ->whereNull('party_type')->whereNull('party_id')
                ->whereNull('dim_warehouse_id')->whereNull('dim_shop_id')
                ->delete();

            // Previous closed period (cùng kind) — opening = closing previous.
            $prev = FiscalPeriod::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('kind', $period->kind)
                ->where('end_date', '<', $period->start_date)
                ->orderBy('end_date', 'desc')
                ->first();
            $openingsByAccount = [];
            if ($prev !== null) {
                $rows = AccountBalance::query()
                    ->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)
                    ->where('period_id', $prev->id)
                    ->whereNull('party_type')->whereNull('party_id')
                    ->whereNull('dim_warehouse_id')->whereNull('dim_shop_id')
                    ->get(['account_id', 'closing']);
                foreach ($rows as $r) {
                    $openingsByAccount[(int) $r->account_id] = (int) $r->closing;
                }
            }

            $now = now();
            $inserts = [];
            // Đi qua tất cả TK postable (kể cả TK không phát sinh — vẫn cần row 0 cho báo cáo).
            $accountsToWrite = $accounts->where('is_postable', true);
            $sumsByAccount = $sums->keyBy('account_id');
            foreach ($accountsToWrite as $acc) {
                $sum = $sumsByAccount->get($acc->id);
                $debit = (int) ($sum->dr ?? 0);
                $credit = (int) ($sum->cr ?? 0);
                $opening = $openingsByAccount[$acc->id] ?? 0;
                $closing = $acc->isDebitNormal()
                    ? ($opening + $debit - $credit)
                    : ($opening + $credit - $debit);
                if ($debit === 0 && $credit === 0 && $opening === 0 && $closing === 0) {
                    continue; // skip rows toàn 0 để tiết kiệm
                }
                $inserts[] = [
                    'tenant_id' => $tenantId,
                    'account_id' => $acc->id,
                    'period_id' => $period->id,
                    'party_type' => null,
                    'party_id' => null,
                    'dim_warehouse_id' => null,
                    'dim_shop_id' => null,
                    'opening' => $opening,
                    'debit' => $debit,
                    'credit' => $credit,
                    'closing' => $closing,
                    'recomputed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (! empty($inserts)) {
                AccountBalance::query()->insert($inserts);
            }

            return count($inserts);
        });
    }

    /**
     * Tính tổng phát sinh + closing cho 1 TK ở 1 kỳ (live query, không qua materialized).
     * Dùng ở Drawer "Sổ chi tiết" khi user kéo cột tổng nhanh.
     */
    public function liveSnapshot(int $tenantId, int $accountId, FiscalPeriod $period): array
    {
        $row = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_id', $accountId)
            ->whereBetween('posted_at', [
                $period->start_date->copy()->startOfDay(),
                $period->end_date->copy()->endOfDay(),
            ])
            ->selectRaw('COALESCE(SUM(dr_amount),0) as dr, COALESCE(SUM(cr_amount),0) as cr')
            ->first();

        return [
            'debit' => (int) ($row->dr ?? 0),
            'credit' => (int) ($row->cr ?? 0),
        ];
    }
}
