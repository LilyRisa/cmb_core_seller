<?php

/*
|--------------------------------------------------------------------------
| Customers module — internal buyer registry & reputation (SPEC 0002).
|--------------------------------------------------------------------------
*/

return [

    // Reputation heuristic v1 — see SPEC 0002 §4.4. Tune cautiously; it drives a
    // hint shown to staff, not an automatic action.
    'reputation' => [
        'base' => 100,
        'per_cancellation' => 15,
        'per_delivery_failed' => 10,
        'per_return' => 8,
        'per_completed' => 2,
        'completed_bonus_cap' => 30,
        'watch_min' => 40,     // score >= this and < ok_min  ⇒ "watch"
        'ok_min' => 80,        // score >= this               ⇒ "ok"
        'vip_min_completed' => 10,
        'vip_max_cancellation_rate' => 0.05,
    ],

    // Auto-note thresholds (count at which a note is added once). dedupe_key keeps
    // it idempotent. See SPEC 0002 §4.5.
    'auto_notes' => [
        'cancel_warning_at' => 2,
        'cancel_danger_at' => 5,
        'delivery_failed_warning_at' => 2,
        'return_warning_at' => 3,
        'vip_at' => 10,
    ],

    // Keep at most N distinct recent shipping addresses on the customer record.
    'max_addresses' => 5,

    // Days after a shop is disconnected before its single-shop customers are
    // anonymized (long enough for disputes / reconciliation). Per-tenant override
    // lands in Settings (Phase 6).
    'anonymize_after_days' => 90,
];
