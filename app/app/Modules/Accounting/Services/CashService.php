<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\CashAccount;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * CRUD cash accounts + tính số dư từ journal_lines của TK GL liên kết.
 * Phase 7.4 — SPEC 0019.
 */
class CashService
{
    /**
     * @param  array{code:string,name:string,kind:string,gl_account_code:string,bank_name?:string,account_no?:string,account_holder?:string,description?:string}  $payload
     */
    public function create(int $tenantId, array $payload): CashAccount
    {
        $code = (string) $payload['code'];
        if (CashAccount::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->where('code', $code)->exists()) {
            throw AccountingException::invalidLines("Mã quỹ/TK {$code} đã tồn tại.");
        }
        $glAcc = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('code', $payload['gl_account_code'])
            ->where('is_postable', true)->where('is_active', true)
            ->first();
        if (! $glAcc) {
            throw AccountingException::accountNotPostable($payload['gl_account_code']);
        }

        return CashAccount::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $payload['name'],
            'kind' => $payload['kind'],
            'bank_name' => $payload['bank_name'] ?? null,
            'account_no' => $payload['account_no'] ?? null,
            'account_holder' => $payload['account_holder'] ?? null,
            'currency' => 'VND',
            'gl_account_id' => $glAcc->id,
            'is_active' => true,
            'description' => $payload['description'] ?? null,
        ]);
    }

    /** Tổng tồn quỹ — đọc trực tiếp từ journal_lines của TK GL. */
    public function balance(int $tenantId, CashAccount $cash): int
    {
        $row = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_id', $cash->gl_account_id)
            ->selectRaw('COALESCE(SUM(dr_amount),0) as dr, COALESCE(SUM(cr_amount),0) as cr')
            ->first();

        // TK 1111/1121 debit-normal ⇒ balance = dr - cr.
        return (int) ($row->dr ?? 0) - (int) ($row->cr ?? 0);
    }

    /** Map balances cho mọi cash account active. */
    public function balanceMap(int $tenantId): array
    {
        $accounts = CashAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        $glIds = $accounts->pluck('gl_account_id')->unique()->all();
        if (! $glIds) {
            return [];
        }
        $sums = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('account_id', $glIds)
            ->selectRaw('account_id, COALESCE(SUM(dr_amount),0) as dr, COALESCE(SUM(cr_amount),0) as cr')
            ->groupBy('account_id')->get()->keyBy('account_id');
        $out = [];
        foreach ($accounts as $a) {
            $s = $sums->get($a->gl_account_id);
            $out[$a->id] = (int) ($s->dr ?? 0) - (int) ($s->cr ?? 0);
        }

        return $out;
    }
}
