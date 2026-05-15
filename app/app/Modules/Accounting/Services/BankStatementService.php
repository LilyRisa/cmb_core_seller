<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\BankStatement;
use CMBcoreSeller\Modules\Accounting\Models\BankStatementLine;
use CMBcoreSeller\Modules\Accounting\Models\CashAccount;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Import & match sao kê ngân hàng. Phase 7.4 — SPEC 0019.
 *
 * Hỗ trợ CSV/manual rows; matching tự động dựa trên amount + memo (chứa mã GD).
 */
class BankStatementService
{
    /**
     * Import 1 sao kê + nhiều line.
     *
     * @param  array<int, array{txn_date:string, amount:int, counter_party?:string, memo?:string, external_ref?:string}>  $lines
     */
    public function import(int $tenantId, int $cashAccountId, string $periodStart, string $periodEnd, string $importedFrom, array $lines, ?int $userId = null): BankStatement
    {
        $cash = CashAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereKey($cashAccountId)->first();
        if (! $cash) {
            throw AccountingException::invalidLines('Tài khoản quỹ/NH không tồn tại.');
        }

        return DB::transaction(function () use ($tenantId, $cashAccountId, $periodStart, $periodEnd, $importedFrom, $lines, $userId) {
            $totalIn = 0;
            $totalOut = 0;
            foreach ($lines as $l) {
                $amount = (int) $l['amount'];
                if ($amount > 0) {
                    $totalIn += $amount;
                } else {
                    $totalOut += abs($amount);
                }
            }
            $stmt = BankStatement::query()->create([
                'tenant_id' => $tenantId,
                'cash_account_id' => $cashAccountId,
                'period_start' => Carbon::parse($periodStart),
                'period_end' => Carbon::parse($periodEnd),
                'imported_from' => $importedFrom,
                'lines_count' => count($lines),
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'status' => 'imported',
                'created_by' => $userId,
            ]);

            $rows = [];
            $now = now();
            foreach ($lines as $l) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'bank_statement_id' => $stmt->id,
                    'txn_date' => Carbon::parse($l['txn_date']),
                    'amount' => (int) $l['amount'],
                    'counter_party' => $l['counter_party'] ?? null,
                    'memo' => $l['memo'] ?? null,
                    'external_ref' => $l['external_ref'] ?? null,
                    'status' => BankStatementLine::STATUS_UNMATCHED,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) {
                BankStatementLine::query()->insert($rows);
            }

            return $stmt->refresh();
        });
    }

    /**
     * Match 1 dòng sao kê với 1 reference (customer_receipt | vendor_payment | journal_entry).
     */
    public function matchLine(BankStatementLine $line, string $refType, int $refId, ?int $journalEntryId, ?int $userId = null): BankStatementLine
    {
        if ($line->status === BankStatementLine::STATUS_MATCHED) {
            return $line;
        }
        if (! in_array($refType, ['customer_receipt', 'vendor_payment', 'journal_entry'], true)) {
            throw AccountingException::invalidLines("ref_type không hợp lệ: {$refType}");
        }
        $line->forceFill([
            'status' => BankStatementLine::STATUS_MATCHED,
            'matched_ref_type' => $refType,
            'matched_ref_id' => $refId,
            'matched_journal_entry_id' => $journalEntryId,
            'matched_at' => now(),
            'matched_by' => $userId,
        ])->save();

        return $line;
    }

    public function ignoreLine(BankStatementLine $line, ?int $userId = null): BankStatementLine
    {
        $line->forceFill([
            'status' => BankStatementLine::STATUS_IGNORED,
            'matched_at' => now(),
            'matched_by' => $userId,
        ])->save();

        return $line;
    }
}
