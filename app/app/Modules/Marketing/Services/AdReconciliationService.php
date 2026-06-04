<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Orders\Contracts\ManualOrderDailyStats;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Daily reconciliation: account-level ad metrics (spend / Messenger conversations /
 * leads) vs manual orders created that day. Attribution is day/account aggregate
 * (manual orders aren't tagged per campaign). No AI here — pure data.
 */
class AdReconciliationService
{
    public function __construct(private ManualOrderDailyStats $manualStats) {}

    /**
     * @return list<array{date:string, spend:int, conversations:int, leads:int,
     *   manual_orders:int, manual_revenue:int, cost_per_conversation:?int,
     *   cost_per_order:?int, conv_to_order_pct:?float}>
     */
    public function reconcile(AdAccount $account, int $days = 14): array
    {
        $to = CarbonImmutable::now()->startOfDay();
        $from = $to->subDays(max(1, $days) - 1);

        // Account-level daily snapshots in range, summed per date (≈1 row/date).
        $ads = [];
        $snaps = AdInsightSnapshot::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('level', 'account')
            ->whereBetween('date_start', [$from->toDateString(), $to->toDateString()])
            ->get();
        foreach ($snaps as $s) {
            $d = (string) $s->date_start;
            $ads[$d] ??= ['spend' => 0, 'conversations' => 0, 'leads' => 0];
            $ads[$d]['spend'] += (int) $s->spend;
            $ads[$d]['conversations'] += (int) $s->messaging_conversations;
            $ads[$d]['leads'] += (int) $s->leads;
        }

        $manual = $this->manualStats->dailyManualStats((int) $account->tenant_id, $from, $to);

        $rows = [];
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $date = $d->toDateString();
            $a = $ads[$date] ?? ['spend' => 0, 'conversations' => 0, 'leads' => 0];
            $m = $manual[$date] ?? ['count' => 0, 'revenue' => 0];
            $conv = (int) $a['conversations'];
            $orders = (int) $m['count'];

            $rows[] = [
                'date' => $date,
                'spend' => (int) $a['spend'],
                'conversations' => $conv,
                'leads' => (int) $a['leads'],
                'manual_orders' => $orders,
                'manual_revenue' => (int) $m['revenue'],
                'cost_per_conversation' => $conv > 0 ? intdiv((int) $a['spend'], $conv) : null,
                'cost_per_order' => $orders > 0 ? intdiv((int) $a['spend'], $orders) : null,
                'conv_to_order_pct' => $conv > 0 ? round($orders / $conv * 100, 2) : null,
            ];
        }

        return $rows;
    }
}
