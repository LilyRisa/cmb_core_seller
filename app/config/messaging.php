<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media storage (SPEC-0024 §5.4 / §8.5, ADR-0020)
    |--------------------------------------------------------------------------
    |
    | Disk lưu media tin nhắn. Mặc định theo `FILESYSTEM_DISK`; production dùng
    | object storage S3-compatible (R2 / MinIO). Prefix mọi key theo tenant:
    |   tenants/{id}/messaging/{yyyy}/{mm}/{conversation_id}/{uuid}.{ext}
    |
    | `signed_url_ttl` — TTL signed URL trả cho FE (giây). 5 phút mặc định; FE
    | re-fetch khi hết hạn (cấm hot-link cross-tenant — §8.5).
    |
    */
    'media_disk' => env('MESSAGING_MEDIA_DISK', env('FILESYSTEM_DISK', 'local')),

    'signed_url_ttl' => (int) env('MESSAGING_SIGNED_URL_TTL', 300),

    /*
    | Giới hạn upload outbound + relay inbound (byte). SPEC-0024 §7.
    | Sai size / MIME ⇒ 422 ATTACHMENT_INVALID.
    */
    'limits' => [
        'image' => (int) env('MESSAGING_MAX_IMAGE_MB', 25) * 1024 * 1024,
        'video' => (int) env('MESSAGING_MAX_VIDEO_MB', 100) * 1024 * 1024,
        'file' => (int) env('MESSAGING_MAX_FILE_MB', 25) * 1024 * 1024,
        'audio' => (int) env('MESSAGING_MAX_AUDIO_MB', 25) * 1024 * 1024,
    ],

    /*
    | MIME whitelist per kind. Validate cả FE lẫn BE.
    */
    'allowed_mime' => [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/quicktime', 'video/webm', 'video/3gpp'],
        'audio' => ['audio/mpeg', 'audio/mp4', 'audio/aac', 'audio/ogg', 'audio/amr'],
        'file' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI auto-reply (SPEC-0024 §4.6)
    |--------------------------------------------------------------------------
    |
    | `auto_reply_debounce_seconds` — gộp các tin khách gửi LIÊN TIẾP (text rời,
    | hoặc Facebook tách text + ảnh thành nhiều event) thành MỘT lượt trả lời.
    | Mỗi inbound hẹn 1 job trễ; chỉ tin INBOUND mới nhất mới được trả lời
    | (latest-wins) ⇒ 1 reply / burst. 0 = trả lời ngay (không gộp).
    |
    */
    'ai' => [
        'auto_reply_debounce_seconds' => (int) env('MESSAGING_AI_DEBOUNCE_SECONDS', 4),

        // RAG vector (Qdrant) — cấu hình EMBEDDING. Mặc định TÁI DÙNG endpoint embedding của
        // "Hỏi AI" (HELP_ASSISTANT_EMBEDDING_*) — TÁCH khỏi provider CHAT của tenant (tránh 403
        // khi cổng chat không phục vụ /v1/embeddings). Có base_url+api_key ⇒ embed qua endpoint
        // này; trống ⇒ thử embed qua provider chat của tenant; lỗi ⇒ rơi về keyword. Đổi model
        // ⇒ `messaging:kb-reindex --fresh`.
        'embedding' => [
            'base_url' => env('MESSAGING_AI_EMBEDDING_BASE_URL', env('HELP_ASSISTANT_EMBEDDING_BASE_URL', '')),
            'api_key' => env('MESSAGING_AI_EMBEDDING_API_KEY', env('HELP_ASSISTANT_EMBEDDING_API_KEY', '')),
            'model' => env('MESSAGING_AI_EMBEDDING_MODEL', env('HELP_ASSISTANT_EMBEDDING_MODEL', 'text-embedding-3-small')),
        ],
    ],
];
