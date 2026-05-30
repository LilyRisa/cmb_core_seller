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
        // Code AI provider RIÊNG cho Support (tách hẳn provider messaging của tenant).
        // BẬT MẶC ĐỊNH = 'support'; row `ai_providers` tương ứng được TỰ provision từ env
        // bên dưới khi có `HELP_ASSISTANT_API_KEY` (xem SupportProviderProvisioner).
        'provider_code' => env('HELP_ASSISTANT_PROVIDER', 'support'),
        // Provider RIÊNG cho embedding (RAG). Rỗng ⇒ dùng chung `provider_code`.
        // Cần khi provider chat không có embeddings API (vd OpenRouter).
        'embedding_provider_code' => env('HELP_ASSISTANT_EMBEDDING_PROVIDER', ''),
        'embedding_model' => env('HELP_ASSISTANT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        // Dimension mặc định cho text-embedding-3-small (tạo collection khi chưa probe được).
        'embedding_dim' => (int) env('HELP_ASSISTANT_EMBEDDING_DIM', 1536),
        'top_k' => 5,
        'max_tokens' => 700,

        // Credential cho provider Support tự provision (adapter openai_compatible).
        // CHỈ tạo/cập nhật row khi `api_key` có giá trị — không thì widget chạy keyword.
        'api_key' => env('HELP_ASSISTANT_API_KEY', ''),
        'base_url' => env('HELP_ASSISTANT_BASE_URL', 'https://api.openai.com'),
        'chat_model' => env('HELP_ASSISTANT_MODEL', 'gpt-4o-mini'),
    ],

    // Đường dẫn thư mục chứa rag_chunks.jsonl.
    //  - Dev: docs_user/ ở gốc repo (nguồn sự thật, regenerate được) — nằm NGOÀI app/.
    //  - Prod: docs_user/ KHÔNG vào image (context build = ./app) ⇒ dùng bản ship sẵn
    //    trong app/resources/help/ (copy từ docs_user). Đặt HELP_DOCS_PATH để ghi đè.
    'docs_path' => env('HELP_DOCS_PATH')
        ?: (is_dir(base_path('../docs_user')) ? base_path('../docs_user') : base_path('resources/help')),
];
