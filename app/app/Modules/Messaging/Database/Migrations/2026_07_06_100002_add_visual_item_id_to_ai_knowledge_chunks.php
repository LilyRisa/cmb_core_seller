<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cho phép 1 chunk thuộc document HOẶC visual item (kho tri thức gộp).
 *
 * `document_id` bỏ NOT NULL bằng `->change()` native (Laravel 11.51 không còn
 * phụ thuộc `doctrine/dbal` cho schema change — kể cả SQLite table-rebuild),
 * nên relax được NOT NULL trên CẢ SQLite lẫn Postgres bằng cùng một API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_knowledge_chunks', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->change();
        });

        Schema::table('ai_knowledge_chunks', function (Blueprint $table) {
            $table->unsignedBigInteger('visual_item_id')->nullable()->after('document_id');
            $table->index(['tenant_id', 'visual_item_id'], 'akc_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_knowledge_chunks', function (Blueprint $table) {
            $table->dropIndex('akc_item_idx');
            $table->dropColumn('visual_item_id');
            // document_id giữ nullable — không revert để tránh vỡ dữ liệu item.
        });
    }
};
