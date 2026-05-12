<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media disk
    |--------------------------------------------------------------------------
    |
    | Filesystem disk used for user-uploaded media (SKU/product images, …).
    | In production this is "r2" (Cloudflare R2 — see config/filesystems.php and
    | docs/07-ops/cloudflare-r2-uploads.md). Locally/in tests the default "public"
    | disk works without any cloud credentials.
    |
    */

    'disk' => env('MEDIA_DISK', env('APP_ENV') === 'production' ? 'r2' : 'public'),

    /*
    | Image upload constraints (used by MediaUploader / FormRequest rules).
    */
    'images' => [
        'max_kb' => (int) env('MEDIA_IMAGE_MAX_KB', 5120),   // 5 MB
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

];
