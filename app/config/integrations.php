<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled marketplace channels
    |--------------------------------------------------------------------------
    |
    | Provider codes whose connectors should be loaded into ChannelRegistry.
    | Add 'tiktok', 'shopee', 'lazada' here once their connectors exist and
    | are approved on the respective open platforms. 'manual' is always on.
    |
    */
    'channels' => array_filter(explode(',', (string) env('INTEGRATIONS_CHANNELS', 'manual'))),

    /*
    |--------------------------------------------------------------------------
    | Enabled shipping carriers
    |--------------------------------------------------------------------------
    |
    | Carrier codes whose connectors should be loaded into CarrierRegistry,
    | e.g. ghn,ghtk,jt,viettelpost,ninjavan,spx,vnpost,best,ahamove.
    |
    */
    'carriers' => array_filter(explode(',', (string) env('INTEGRATIONS_CARRIERS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Default carrier for self-fulfilled orders
    |--------------------------------------------------------------------------
    */
    'default_carrier' => env('INTEGRATIONS_DEFAULT_CARRIER'),

    /*
    |--------------------------------------------------------------------------
    | Per-provider request throttling (calls per minute, per shop)
    |--------------------------------------------------------------------------
    |
    | Used by the Redis rate limiter when calling marketplace APIs so one
    | shop/tenant cannot starve the shared queue. Tune per real limits.
    |
    */
    'throttle' => [
        'tiktok' => (int) env('THROTTLE_TIKTOK_PER_MIN', 600),
        'shopee' => (int) env('THROTTLE_SHOPEE_PER_MIN', 600),
        'lazada' => (int) env('THROTTLE_LAZADA_PER_MIN', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order sync
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'poll_interval_minutes' => (int) env('SYNC_POLL_INTERVAL_MINUTES', 10),
        'poll_overlap_minutes' => (int) env('SYNC_POLL_OVERLAP_MINUTES', 5),
        'backfill_days' => (int) env('SYNC_BACKFILL_DAYS', 90),
        'reverse_stock_check' => (bool) env('SYNC_REVERSE_STOCK_CHECK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock push
    |--------------------------------------------------------------------------
    */
    'stock' => [
        'push_debounce_seconds' => (int) env('STOCK_PUSH_DEBOUNCE_SECONDS', 10),
        'default_safety_stock' => (int) env('STOCK_DEFAULT_SAFETY', 0),
    ],

];
