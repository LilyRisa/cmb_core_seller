<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

class AiUsageReportService implements AiUsageReporter
{
    public function usageForUsers(array $userIds): array
    {
        $out = [];
        foreach ($userIds as $id) {
            $out[(int) $id] = ['this_month' => 0, 'all_time' => 0];
        }
        if ($userIds === []) {
            return $out;
        }

        $ym = (int) now()->format('Ym');
        $rows = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->selectRaw('user_id, SUM(count) as all_time, SUM(CASE WHEN period_ym = ? THEN count ELSE 0 END) as this_month', [$ym])
            ->whereIn('user_id', array_map('intval', $userIds))
            ->groupBy('user_id')
            ->toBase()
            ->get();

        foreach ($rows as $r) {
            $out[(int) $r->user_id] = ['this_month' => (int) $r->this_month, 'all_time' => (int) $r->all_time];
        }

        return $out;
    }

    public function breakdownForUser(int $userId): array
    {
        $base = AiUsageCounter::withoutGlobalScope(TenantScope::class)->where('user_id', $userId);

        $byMonth = (clone $base)
            ->selectRaw('period_ym, SUM(count) as count')
            ->groupBy('period_ym')->orderByDesc('period_ym')->get()
            ->map(fn ($r) => ['period_ym' => (int) $r->period_ym, 'count' => (int) $r->count])->all();

        $byFeature = (clone $base)
            ->selectRaw('feature, SUM(count) as count')
            ->groupBy('feature')->orderByDesc('count')->get()
            ->map(fn ($r) => ['feature' => (string) $r->feature, 'count' => (int) $r->count])->all();

        return [
            'all_time' => array_sum(array_column($byFeature, 'count')),
            'by_month' => $byMonth,
            'by_feature' => $byFeature,
        ];
    }

    public function breakdownForTenant(int $tenantId): array
    {
        $base = AiUsageCounter::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId);

        $byMonth = (clone $base)
            ->selectRaw('period_ym, SUM(count) as count')
            ->groupBy('period_ym')->orderByDesc('period_ym')->get()
            ->map(fn ($r) => ['period_ym' => (int) $r->period_ym, 'count' => (int) $r->count])->all();

        $byFeature = (clone $base)
            ->selectRaw('feature, SUM(count) as count')
            ->groupBy('feature')->orderByDesc('count')->get()
            ->map(fn ($r) => ['feature' => (string) $r->feature, 'count' => (int) $r->count])->all();

        return [
            'all_time' => array_sum(array_column($byFeature, 'count')),
            'by_month' => $byMonth,
            'by_feature' => $byFeature,
        ];
    }

    public function topTenantsByUsageThisMonth(int $limit): array
    {
        $ym = (int) now()->format('Ym');
        $rows = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->selectRaw('tenant_id, SUM(count) as calls_this_month')
            ->where('period_ym', $ym)
            ->groupBy('tenant_id')
            ->orderByDesc('calls_this_month')
            ->limit($limit)
            ->toBase()
            ->get();

        return $rows->map(fn ($r) => [
            'tenant_id' => (int) $r->tenant_id,
            'calls_this_month' => (int) $r->calls_this_month,
        ])->all();
    }

    public function totalCallsThisMonth(): int
    {
        $ym = (int) now()->format('Ym');

        return (int) AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->where('period_ym', $ym)
            ->sum('count');
    }
}
