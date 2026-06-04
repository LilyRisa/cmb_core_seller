<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Marketing (Facebook Ads) — SPEC 2026-06-04
    |--------------------------------------------------------------------------
    */

    // AI forecast cooldown (minutes): within this window a regenerate request
    // returns the cached forecast WITHOUT calling the AI (saves quota).
    'forecast_cooldown_minutes' => (int) env('MARKETING_FORECAST_COOLDOWN_MINUTES', 360),
];
