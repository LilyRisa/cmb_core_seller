<?php

namespace CMBcoreSeller\Modules\Customers\Support;

/**
 * Heuristic, rule-based buyer reputation (v1). Deliberately simple & explainable
 * — the seller needs to understand *why* a buyer is flagged. Tunable via
 * config('customers.reputation'). See SPEC 0002 §4.4.
 *
 * Not ML, not cross-tenant — backlog post-Phase 7.
 */
final class ReputationCalculator
{
    /**
     * @param  array<string,int|float>  $stats  lifetime_stats (see SPEC 0002 §4.3)
     * @return array{score:int,label:string,is_vip:bool}
     */
    public static function evaluate(array $stats, bool $isBlocked = false): array
    {
        $cfg = (array) config('customers.reputation', []);
        $base = (int) ($cfg['base'] ?? 100);
        $perCancel = (int) ($cfg['per_cancellation'] ?? 15);
        $perDelivFail = (int) ($cfg['per_delivery_failed'] ?? 10);
        $perReturn = (int) ($cfg['per_return'] ?? 8);
        $perCompleted = (int) ($cfg['per_completed'] ?? 2);
        $completedBonusCap = (int) ($cfg['completed_bonus_cap'] ?? 30);
        $watchMin = (int) ($cfg['watch_min'] ?? 40);
        $okMin = (int) ($cfg['ok_min'] ?? 80);
        $vipMinCompleted = (int) ($cfg['vip_min_completed'] ?? 10);
        $vipMaxCancelRate = (float) ($cfg['vip_max_cancellation_rate'] ?? 0.05);

        $cancelled = (int) ($stats['orders_cancelled'] ?? 0);
        $delivFail = (int) ($stats['orders_delivery_failed'] ?? 0);
        $returned = (int) ($stats['orders_returned'] ?? 0);
        $completed = (int) ($stats['orders_completed'] ?? 0);
        $total = (int) ($stats['orders_total'] ?? 0);

        $score = $base
            - $perCancel * $cancelled
            - $perDelivFail * $delivFail
            - $perReturn * $returned
            + min($completedBonusCap, $perCompleted * $completed);
        $score = max(0, min(100, $score));

        if ($isBlocked) {
            $label = 'blocked';
        } elseif ($score >= $okMin) {
            $label = 'ok';
        } elseif ($score >= $watchMin) {
            $label = 'watch';
        } else {
            $label = 'risk';
        }

        $cancelRate = $total > 0 ? $cancelled / $total : 0.0;
        $isVip = $completed >= $vipMinCompleted && $cancelRate <= $vipMaxCancelRate;

        return ['score' => $score, 'label' => $label, 'is_vip' => $isVip];
    }
}
