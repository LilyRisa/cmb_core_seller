<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Orders\Contracts\ManualOrderDailyStats;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

class ManualOrderDailyStatsService implements ManualOrderDailyStats
{
    public function dailyManualStats(int $tenantId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('source', 'manual')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('date(created_at) as d, count(*) as c, coalesce(sum(grand_total),0) as rev')
            ->groupBy('d')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->getAttribute('d')] = [
                'count' => (int) $r->getAttribute('c'),
                'revenue' => (int) $r->getAttribute('rev'),
            ];
        }

        return $out;
    }
}
