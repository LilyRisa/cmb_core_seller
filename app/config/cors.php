<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) — SPEC 0029 (mobile API access)
|--------------------------------------------------------------------------
|
| Native Bearer clients (Expo trên iOS/Android) KHÔNG kích hoạt CORS, nhưng
| Expo Web (test trên trình duyệt) thì có — cần cho phép header `Authorization`
| + `X-Tenant-Id`. Origin được điều khiển bằng env `CORS_ALLOWED_ORIGINS` (CSV),
| mặc định dev `http://localhost:8081` (Expo dev server). `supports_credentials`
| để true để luồng SPA cookie (same-domain React) vẫn hoạt động song song.
|
| Ví dụ:
|   CORS_ALLOWED_ORIGINS=http://localhost:8081,http://localhost:3000
|
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:8081')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-Tenant-Id',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-Request-Id',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];
