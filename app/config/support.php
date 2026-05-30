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
        // Rỗng ⇒ tắt vector search, dùng fallback keyword.
        'url' => env('QDRANT_URL', ''),
        'api_key' => env('QDRANT_API_KEY', ''),
        'collection' => env('QDRANT_HELP_COLLECTION', 'omnisell_help'),
        'timeout' => 10,
    ],

    'assistant' => [
        // Code 1 row trong `ai_providers` (super-admin tạo) hỗ trợ embedding + chat.
        // Rỗng ⇒ không gọi LLM, dùng fallback keyword + trả chunk khớp nhất.
        'provider_code' => env('HELP_ASSISTANT_PROVIDER', ''),
        'embedding_model' => env('HELP_ASSISTANT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        // Dimension mặc định cho text-embedding-3-small (tạo collection khi chưa probe được).
        'embedding_dim' => (int) env('HELP_ASSISTANT_EMBEDDING_DIM', 1536),
        'top_k' => 5,
        'max_tokens' => 700,
    ],

    // Đường dẫn thư mục chứa rag_chunks.jsonl.
    //  - Dev: docs_user/ ở gốc repo (nguồn sự thật, regenerate được) — nằm NGOÀI app/.
    //  - Prod: docs_user/ KHÔNG vào image (context build = ./app) ⇒ dùng bản ship sẵn
    //    trong app/resources/help/ (copy từ docs_user). Đặt HELP_DOCS_PATH để ghi đè.
    'docs_path' => env('HELP_DOCS_PATH')
        ?: (is_dir(base_path('../docs_user')) ? base_path('../docs_user') : base_path('resources/help')),
];
