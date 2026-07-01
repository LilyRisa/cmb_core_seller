<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tăng tốc thanh tìm kiếm hội thoại (LIKE/ILIKE '%..%' trên tên, SĐT, preview và
 * nội dung tin nhắn). LIKE có wildcard đầu chuỗi không dùng được B-tree, nên trên
 * Postgres ta dựng GIN trigram (pg_trgm). SQLite/MySQL dev bỏ qua — dữ liệu nhỏ, LIKE thường đủ.
 *
 * ⚠️ Nếu bảng `messages` đã bật partition theo tháng ở prod, CREATE INDEX CONCURRENTLY
 * KHÔNG chạy trên bảng cha partitioned — khi đó tạo index theo từng partition (hoặc
 * bỏ CONCURRENTLY, chịu lock ngắn khi bảo trì). Kiểm tra trạng thái partition trước khi migrate tay.
 */
return new class extends Migration
{
    // CREATE INDEX CONCURRENTLY không chạy trong transaction.
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_messages_body_trgm ON messages USING gin (body gin_trgm_ops)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_conversations_buyer_name_trgm ON conversations USING gin (buyer_name gin_trgm_ops)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_conversations_preview_trgm ON conversations USING gin (last_message_preview gin_trgm_ops)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_conversations_phone_trgm ON conversations USING gin (detected_phone gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ix_messages_body_trgm');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ix_conversations_buyer_name_trgm');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ix_conversations_preview_trgm');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ix_conversations_phone_trgm');
    }
};
