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
];
