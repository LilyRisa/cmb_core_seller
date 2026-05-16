<?php

/*
 * Notifications module config (SPEC 0022 — Phase 6.5).
 *
 * Hiện chỉ có kênh `mail`. Phase 6.5 sub-task tiếp theo sẽ thêm Zalo OA / Telegram /
 * In-app / Web push — gom config kênh ở đây để tenant có thể bật/tắt sau này.
 */

return [
    'brand' => [
        'name' => env('NOTIFICATIONS_BRAND_NAME', 'CMBcoreSeller'),
        'tagline' => env('NOTIFICATIONS_BRAND_TAGLINE', 'Quản lý bán hàng đa sàn cho thị trường Việt Nam'),
        'support_email' => env('NOTIFICATIONS_SUPPORT_EMAIL', 'support@cmbcore.com'),
        'primary_color' => env('NOTIFICATIONS_PRIMARY_COLOR', '#10B981'),
        'accent_color' => env('NOTIFICATIONS_ACCENT_COLOR', '#059669'),
        'logo_url' => env('NOTIFICATIONS_LOGO_URL'),
    ],

    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),

    'queue' => env('NOTIFICATIONS_QUEUE', 'notifications'),

    'mail' => [
        'verify_throttle_per_hour' => 6,
        'reset_throttle_per_15min' => 5,
    ],
];
