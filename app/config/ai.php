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
        // Trần token cho phân loại intent. Phải đủ rộng cho model SUY LUẬN (reasoning) sinh
        // khối <think>…</think> rồi mới tới nhãn — quá nhỏ (8/16) cắt cụt ⇒ luôn "other".
        'classify_max_tokens' => (int) env('AI_HTTP_CLASSIFY_MAX_TOKENS', 1024),
        'embed_timeout' => (int) env('AI_HTTP_EMBED_TIMEOUT', 90),
        'retries' => (int) env('AI_HTTP_RETRIES', 1),
        'retry_backoff_ms' => (int) env('AI_HTTP_RETRY_BACKOFF_MS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vision (đa phương thức) — gửi ảnh khách kèm tin lên AI để phân tích
    |--------------------------------------------------------------------------
    |
    | Chỉ provider có `vision_verified=true` (xác minh runtime qua super-admin, KHÔNG
    | gate theo tên model) mới đính ảnh vào request; provider khác giữ placeholder text.
    |
    | `inline_base64` MẶC ĐỊNH true ⇒ nhúng base64 ảnh (Claude + OpenAI đều hiểu). Lý do:
    | nhiều cổng OpenAI-compatible (vd vilao.ai) KHÔNG tự tải ảnh từ URL → model báo
    | "chưa thấy ảnh". Base64 chạy với mọi provider. Đặt =false nếu provider tự fetch URL
    | tốt và muốn giảm payload (OpenAI/Anthropic chính chủ). Có giới hạn `inline_max_kb`.
    */
    'vision' => [
        'enabled' => (bool) env('AI_VISION_ENABLED', true),
        'max_images_per_message' => (int) env('AI_VISION_MAX_IMAGES_PER_MESSAGE', 3),
        // Nhúng base64 thay vì link — mặc định BẬT (cổng OpenAI-compatible thường không fetch URL).
        'inline_base64' => filter_var(env('AI_VISION_INLINE_BASE64', true), FILTER_VALIDATE_BOOLEAN),
        // Bỏ qua ảnh > ngưỡng khi nhúng base64 (KB) để tránh phình request.
        'inline_max_kb' => (int) env('AI_VISION_INLINE_MAX_KB', 4096),
        // Trần token cho analyzeImages. Model reasoning (vd nemotron omni) tiêu token
        // "suy nghĩ" trước khi ra JSON ⇒ 300 dễ cắt cụt. Nới rộng để hoàn tất.
        'max_tokens' => (int) env('AI_VISION_MAX_TOKENS', 2048),
    ],
];
