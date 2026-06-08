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

    // Marketing AI (phân tích quảng cáo) HTTP client — chạy NỀN trên queue `marketing-ai`
    // (supervisor timeout 600s). Phân tích sinh nội dung dài, provider buffer cả response
    // (non-streaming) ⇒ timeout tổng phải đủ lớn, nếu không client đóng kết nối giữa chừng
    // ("Client closed the connection before the stream finished"). connect_timeout ngắn để
    // fail-fast khi provider chết. Worst case = http_timeout × (http_retries+1) PHẢI < 600s.
    'ai' => [
        'http_timeout' => (int) env('MARKETING_AI_HTTP_TIMEOUT', 180),
        'http_connect_timeout' => (int) env('MARKETING_AI_HTTP_CONNECT_TIMEOUT', 10),
        'http_retries' => (int) env('MARKETING_AI_HTTP_RETRIES', 1),
        'http_retry_backoff_ms' => (int) env('MARKETING_AI_HTTP_RETRY_BACKOFF_MS', 2000),
    ],
];
