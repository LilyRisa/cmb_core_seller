<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Database\Seeders\AccountingPostRulesSeeder;
use CMBcoreSeller\Modules\Accounting\Database\Seeders\ChartAccountsTT133Seeder;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

/**
 * Onboard module Kế toán cho 1 tenant. Idempotent. Phase 7.1 — SPEC 0019.
 *
 * Một tenant chỉ cần gọi 1 lần khi nâng gói lên Pro/Business; gọi lại = no-op an toàn.
 */
class AccountingSetupService
{
    public function __construct(
        private readonly ChartAccountsTT133Seeder $coa,
        private readonly AccountingPostRulesSeeder $rules,
        private readonly PeriodService $periods,
    ) {}

    /**
     * @return array{accounts_created:int, rules_created:int, periods_created:int, initialized:bool}
     */
    public function run(int $tenantId, ?int $year = null): array
    {
        $year ??= (int) now()->format('Y');
        $alreadyInit = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->exists();

        return DB::transaction(function () use ($tenantId, $year, $alreadyInit) {
            $accCount = $this->coa->run($tenantId);
            $ruleCount = $this->rules->run($tenantId);
            $periodCount = $this->periods->ensureYear($tenantId, $year);

            return [
                'accounts_created' => $accCount,
                'rules_created' => $ruleCount,
                'periods_created' => $periodCount,
                'initialized' => $alreadyInit, // true nếu đã từng setup trước đó
            ];
        });
    }

    public function isInitialized(int $tenantId): bool
    {
        return ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->exists();
    }
}
