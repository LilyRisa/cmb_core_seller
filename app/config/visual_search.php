<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Visual training & tìm sản phẩm bằng ảnh (SPEC 2026-06-16)
    |--------------------------------------------------------------------------
    |
    | Recall = embedding ẢNH (config/integrations.php['image_embedding']) trên Qdrant
    | (config/integrations.php['vector']); precision = vision LLM re-rank. Tách biệt
    | luồng AI reply tối ưu — lỗi/tắt ⇒ not_found (không ném).
    |
    */

    // Là MỘT PHẦN của AI tự động trả lời — dùng chung feature gói `messaging_ai`
    // (KHÔNG tách feature riêng). Route + tiêu thụ đều gate qua messaging_ai.
    'feature' => 'messaging_ai',

    // Disk lưu ảnh training (mặc định theo messaging media / filesystem).
    'media_disk' => env('VISUAL_SEARCH_MEDIA_DISK', env('MESSAGING_MEDIA_DISK', env('FILESYSTEM_DISK', 'local'))),

    // Tên collection Qdrant cơ sở; collection vật lý = "{prefix}__{modelKey}".
    'collection_prefix' => env('VISUAL_SEARCH_COLLECTION_PREFIX', 'visual_training'),

    'match' => [
        'top_k_images' => (int) env('VISUAL_SEARCH_TOP_K_IMAGES', 20),
        'top_n_items' => (int) env('VISUAL_SEARCH_TOP_N_ITEMS', 5),
        // Bỏ ảnh dưới ngưỡng recall (cosine normalized 0..1).
        'recall_floor' => (float) env('VISUAL_SEARCH_RECALL_FLOOR', 0.20),
        // Ngưỡng tối thiểu để coi là "matched" (khi không re-rank).
        'match_min' => (float) env('VISUAL_SEARCH_MATCH_MIN', 0.30),
        // top1 - top2 < delta ⇒ ambiguous (không auto chọn).
        'ambiguous_delta' => (float) env('VISUAL_SEARCH_AMBIGUOUS_DELTA', 0.03),
        // Cách gộp điểm các ảnh của 1 item: 'max' | 'mean'.
        'aggregate' => env('VISUAL_SEARCH_AGGREGATE', 'max'),
    ],

    'rerank' => [
        'enabled' => (bool) env('VISUAL_SEARCH_RERANK', true),
    ],

    'image' => [
        'max_size_kb' => (int) env('VISUAL_SEARCH_IMAGE_MAX_KB', 8192),
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_per_item' => (int) env('VISUAL_SEARCH_MAX_IMAGES_PER_ITEM', 12),
    ],
];
