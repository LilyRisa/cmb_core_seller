<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trợ lý trợ giúp sản phẩm (Help assistant) — module Support
    |--------------------------------------------------------------------------
    |
    | RAG hỏi-đáp về CÁCH DÙNG hệ thống (khác knowledge messaging của tenant).
    | Index từ docs_user/rag_chunks.jsonl vào Qdrant. Provider AI dùng RIÊNG
    | (không dùng provider messaging của tenant). Thiếu Qdrant/provider ⇒ fallback
    | keyword trên bảng help_chunks (KHÔNG bao giờ lỗi 500).
    */

    'qdrant' => [
        // BẬT MẶC ĐỊNH (trỏ service `qdrant` trong docker). Đặt rỗng để tắt vector search
        // ⇒ fallback keyword. Qdrant không chạy ⇒ tự suy biến mượt (không lỗi).
        'url' => env('QDRANT_URL', 'http://qdrant:6333'),
        'api_key' => env('QDRANT_API_KEY', ''),
        'collection' => env('QDRANT_HELP_COLLECTION', 'omnisell_help'),
        'timeout' => 10,
    ],

    'assistant' => [
        'top_k' => 5,
        'max_tokens' => 700,
        // Dimension mặc định (tạo Qdrant collection khi chưa probe được vector thật).
        'embedding_dim' => (int) env('HELP_ASSISTANT_EMBEDDING_DIM', 1536),

        // Credentials RIÊNG cho Support — TỰ CHỨA, KHÔNG dùng bảng `ai_providers`/registry.
        // CHAT (sinh câu trả lời) — OpenAI-compatible (OpenRouter/OpenAI/...).
        //   base_url = GỐC host, KHÔNG kèm /v1 (client tự thêm /v1/chat/completions).
        'chat' => [
            'base_url' => env('HELP_ASSISTANT_BASE_URL', ''),
            'api_key' => env('HELP_ASSISTANT_API_KEY', ''),
            'model' => env('HELP_ASSISTANT_MODEL', ''),
        ],
        // EMBEDDING (tạo vector RAG) — TÁCH RIÊNG vì provider chat (vd OpenRouter)
        //   thường KHÔNG có /v1/embeddings. Để trống ⇒ tắt vector, chạy keyword.
        'embedding' => [
            'base_url' => env('HELP_ASSISTANT_EMBEDDING_BASE_URL', ''),
            'api_key' => env('HELP_ASSISTANT_EMBEDDING_API_KEY', ''),
            'model' => env('HELP_ASSISTANT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        ],
    ],

    // Đường dẫn thư mục chứa rag_chunks.jsonl.
    //  - Dev: docs_user/ ở gốc repo (nguồn sự thật, regenerate được) — nằm NGOÀI app/.
    //  - Prod: docs_user/ KHÔNG vào image (context build = ./app) ⇒ dùng bản ship sẵn
    //    trong app/resources/help/ (copy từ docs_user). Đặt HELP_DOCS_PATH để ghi đè.
    'docs_path' => env('HELP_DOCS_PATH')
        ?: (is_dir(base_path('../docs_user')) ? base_path('../docs_user') : base_path('resources/help')),
];
