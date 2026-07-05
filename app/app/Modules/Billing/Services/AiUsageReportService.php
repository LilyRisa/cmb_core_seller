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
}
