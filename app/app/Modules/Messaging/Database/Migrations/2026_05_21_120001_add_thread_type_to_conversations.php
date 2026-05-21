<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 2026-05-21: thêm `thread_type` để phân biệt hội thoại Messenger thường
 * với luồng comment bài viết Facebook. Mặc định 'message' cho tất cả row cũ.
 *
 * `thread_type` VALUES: 'message' | 'comment'
 * Index composite `(tenant_id, thread_type)` hỗ trợ lọc inbox theo loại luồng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('thread_type', 16)->default('message')->after('provider');
            $table->index(['tenant_id', 'thread_type']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_tenant_id_thread_type_index');
            $table->dropColumn('thread_type');
        });
    }
};
