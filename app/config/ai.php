<?php

/*
|--------------------------------------------------------------------------
| AI integration layer (CMBcoreSeller\Integrations\Ai) — HTTP client tuning
|--------------------------------------------------------------------------
|
| Dùng cho các connector messaging/CSKH (OpenAI / Claude / Custom HTTP).
|
| - connect_timeout NGẮN: fail-fast khi provider chết/không reachable (không treo cả
|   request người dùng cho hết timeout tổng) → giữ performance.
| - *_timeout là timeout TỔNG (các call non-streaming ⇒ phải phủ TRỌN thời gian sinh
|   nội dung; ngắn quá ⇒ client đóng kết nối giữa chừng "client closed before stream finished").
|   reply là interactive (NV/CSKH đang chờ) ⇒ vừa phải; embed chạy nền (index tri thức) ⇒ rộng tay.
| - retries = số lần thử LẠI; Laravel retry() = (1 + retries) lần thử. retry chỉ kích hoạt
|   trên ConnectionException (timeout/đứt kết nối) — đúng tình huống lỗi đang gặp.
*/

return [
    'http' => [
        'connect_timeout' => (int) env('AI_HTTP_CONNECT_TIMEOUT', 10),
        'reply_timeout' => (int) env('AI_HTTP_REPLY_TIMEOUT', 60),
        'classify_timeout' => (int) env('AI_HTTP_CLASSIFY_TIMEOUT', 30),
        'embed_timeout' => (int) env('AI_HTTP_EMBED_TIMEOUT', 90),
        'retries' => (int) env('AI_HTTP_RETRIES', 1),
        'retry_backoff_ms' => (int) env('AI_HTTP_RETRY_BACKOFF_MS', 1000),
    ],
];
