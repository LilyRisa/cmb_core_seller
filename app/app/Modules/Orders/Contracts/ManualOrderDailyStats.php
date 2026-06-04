<?php

namespace CMBcoreSeller\Modules\Orders\Contracts;

use Carbon\CarbonImmutable;

/**
 * Read-only daily stats of manual orders (`source='manual'`) — exposed for other
 * modules (e.g. Marketing) to reconcile against, without touching Orders internals
 * (module dependency rule: communicate via Contracts).
 */
interface ManualOrderDailyStats
{
    /**
     * Count + revenue (grand_total) of manual orders created per day in [$from, $to].
     *
     * @return array<string, array{count:int, revenue:int}> keyed by 'YYYY-MM-DD'
     */
    public function dailyManualStats(int $tenantId, CarbonImmutable $from, CarbonImmutable $to): array;
}
