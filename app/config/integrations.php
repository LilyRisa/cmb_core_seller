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

    /*
    |--------------------------------------------------------------------------
    | TikTok Shop Partner API (see docs/04-channels/tiktok-shop.md, SPEC 0001)
    |--------------------------------------------------------------------------
    |
    | Sandbox vs production is just configuration: point base_url / auth_base_url
    | at the sandbox endpoints (or create a test app/seller in Partner Center)
    | and set TIKTOK_APP_KEY / TIKTOK_APP_SECRET to the sandbox credentials.
    | API version is pinned ("202309" generation); bump deliberately with tests.
    | This is the ONLY place TikTok-specific config / status & event maps live.
    |
    */
    'tiktok' => [
        'app_key' => env('TIKTOK_APP_KEY'),
        'app_secret' => env('TIKTOK_APP_SECRET'),
        // Partner "service" id used to build the seller authorization URL.
        'service_id' => env('TIKTOK_SERVICE_ID'),
        'sandbox' => (bool) env('TIKTOK_SANDBOX', env('TIKTOK_APP_SANDBOX', true)),

        // Hosts. Open API (signed, shop-scoped) vs auth host (token get/refresh)
        // vs the seller authorization page.
        'base_url' => env('TIKTOK_API_BASE_URL', 'https://open-api.tiktokglobalshop.com'),
        'auth_base_url' => env('TIKTOK_AUTH_BASE_URL', 'https://auth.tiktok-shops.com'),
        'authorize_url' => env('TIKTOK_AUTHORIZE_URL', 'https://services.tiktokshop.com/open/authorize'),

        // API version paths.
        'version' => [
            'order' => '202309',
            'authorization' => '202309',
            'event' => '202309',
            'product' => '202309',
            'fulfillment' => '202309',
        ],

        'http' => [
            'timeout' => (int) env('TIKTOK_HTTP_TIMEOUT', 20),
            'retries' => (int) env('TIKTOK_HTTP_RETRIES', 2),
            'retry_sleep_ms' => (int) env('TIKTOK_HTTP_RETRY_SLEEP_MS', 500),
        ],

        // raw_status (TikTok) -> StandardOrderStatus. The single source of truth;
        // see docs/03-domain/order-status-state-machine.md §4. Verify against the
        // Partner API "order status" list when wiring real sandbox data.
        'status_map' => [
            'UNPAID' => 'unpaid',
            'ON_HOLD' => 'processing',
            'AWAITING_SHIPMENT' => 'pending',
            'PARTIALLY_SHIPPING' => 'processing',
            'AWAITING_COLLECTION' => 'ready_to_ship',
            'IN_TRANSIT' => 'shipped',
            'DELIVERED' => 'delivered',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
        ],

        // TikTok webhook "type" (integer) -> normalized WebhookEventDTO type.
        // Unknown types fall back to "unknown" and are recorded as ignored.
        // NOTE: numbers here are best-effort and MUST be verified against the
        // Partner API webhook docs before relying on event-type routing — order
        // events are always re-fetched via fetchOrderDetail, polling is the safety net.
        'webhook_event_types' => [
            1 => 'order_status_update',   // ORDER_STATUS_CHANGE
            2 => 'return_update',         // REVERSE / RETURN status change
            3 => 'order_status_update',   // RECIPIENT_ADDRESS_UPDATE -> re-fetch the order
            4 => 'order_status_update',   // PACKAGE_UPDATE -> re-fetch the order
            5 => 'product_update',        // PRODUCT_STATUS_CHANGE
            6 => 'shop_deauthorized',     // SELLER_DEAUTHORIZATION
            12 => 'order_cancel',         // CANCELLATION_STATUS_CHANGE
            13 => 'return_update',        // RETURN_STATUS_CHANGE
            14 => 'shop_deauthorized',    // AUTHORIZATION_REVOKE / SHOP update
        ],

        // Webhook event names we subscribe this shop to (best-effort; many apps
        // configure events in Partner Center instead).
        'subscribe_events' => array_filter(explode(',', (string) env(
            'TIKTOK_SUBSCRIBE_EVENTS',
            'ORDER_STATUS_CHANGE,RECIPIENT_ADDRESS_UPDATE,PACKAGE_UPDATE,CANCELLATION_STATUS_CHANGE,RETURN_STATUS_CHANGE,SELLER_DEAUTHORIZATION'
        ))),
    ],

];
