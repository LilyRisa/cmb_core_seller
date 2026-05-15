<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Models\AccountingPostRule;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Resolver mapping `event_key` → cặp (debit_account_code, credit_account_code) cho tenant.
 *
 * Listener chỉ cần gọi `resolve($tenantId, 'inventory.goods_receipt.confirmed')` thay vì
 * hardcode TK 156/331 — đúng quyết định 6/7 (tenant chỉnh mapping). Phase 7.1 — SPEC 0019.
 *
 * Memoize per request (cache trong service singleton thông qua $cache).
 */
class PostRuleResolver
{
    /** @var array<int, array<string, array{debit:string, credit:string, enabled:bool}>> */
    private array $cache = [];

    /**
     * @return array{debit:string, credit:string, enabled:bool}|null
     */
    public function resolve(int $tenantId, string $eventKey): ?array
    {
        if (! isset($this->cache[$tenantId])) {
            $rows = AccountingPostRule::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->get(['event_key', 'debit_account_code', 'credit_account_code', 'is_enabled']);
            $map = [];
            foreach ($rows as $r) {
                $map[$r->event_key] = [
                    'debit' => $r->debit_account_code,
                    'credit' => $r->credit_account_code,
                    'enabled' => (bool) $r->is_enabled,
                ];
            }
            $this->cache[$tenantId] = $map;
        }

        return $this->cache[$tenantId][$eventKey] ?? null;
    }

    public function forget(int $tenantId): void
    {
        unset($this->cache[$tenantId]);
    }
}
