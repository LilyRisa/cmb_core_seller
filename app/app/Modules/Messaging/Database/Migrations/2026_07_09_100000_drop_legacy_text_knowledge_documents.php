<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gỡ HOÀN TOÀN hệ RAG tài liệu TEXT thuần cũ (AiKnowledgeDocument). Chỉ còn "Kiến thức" (visual item,
 * chunk theo visual_item_id). Xoá pivot document↔page, bảng documents, và cột document_id + index của
 * nó trên ai_knowledge_chunks (bảng chunk GIỮ LẠI cho visual item).
 *
 * LƯU Ý DỮ LIỆU: xoá vĩnh viễn các tài liệu text cũ. Prod chạy migrate thủ công (RUN_MIGRATIONS=false).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_knowledge_document_page');

        if (Schema::hasColumn('ai_knowledge_chunks', 'document_id')) {
            Schema::table('ai_knowledge_chunks', function (Blueprint $table) {
                // Index cũ theo document_id (tạo ở migration gốc) — bỏ trước khi drop cột.
                try {
                    $table->dropIndex(['tenant_id', 'document_id', 'chunk_index']);
                } catch (Throwable) {
                    // index có thể đã không còn (môi trường khác) — bỏ qua.
                }
                $table->dropColumn('document_id');
            });
        }

        Schema::dropIfExists('ai_knowledge_documents');
    }

    public function down(): void
    {
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('title');
            $table->string('source', 16);
            $table->string('storage_path', 512)->nullable();
            $table->string('url', 1024)->nullable();
            $table->text('inline_text')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->string('embedding_provider_code', 32)->nullable();
            $table->string('embedding_model', 64)->nullable();
            $table->unsignedSmallInteger('embedding_version')->default(1);
            $table->timestamp('indexed_at')->nullable();
            $table->string('status', 16)->default('pending');
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('provider', 32)->default('facebook_page');
            $table->boolean('applies_all_pages')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('ai_knowledge_document_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ai_knowledge_document_id');
            $table->foreignId('channel_account_id');
            $table->timestamps();
            $table->unique(['ai_knowledge_document_id', 'channel_account_id'], 'akd_page_unique');
        });

        if (! Schema::hasColumn('ai_knowledge_chunks', 'document_id')) {
            Schema::table('ai_knowledge_chunks', function (Blueprint $table) {
                $table->foreignId('document_id')->nullable()->after('tenant_id');
                $table->index(['tenant_id', 'document_id', 'chunk_index']);
            });
        }
    }
};
