<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cho phép 1 chunk thuộc document HOẶC visual item (kho tri thức gộp).
 *
 * `document_id` bỏ NOT NULL bằng raw ALTER (không dùng `->change()` để tránh
 * phụ thuộc `doctrine/dbal` — chưa cài trong project). SQLite không ràng buộc
 * NOT NULL chặt qua ALTER nên bỏ qua trên driver này; Postgres cần ALTER thật.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ai_knowledge_chunks ALTER COLUMN document_id DROP NOT NULL');
        }

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
