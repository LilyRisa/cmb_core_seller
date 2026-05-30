<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Support — trợ lý trợ giúp sản phẩm (RAG) + yêu cầu CSKH.
 *
 * - `help_chunks`: bản sao chunk tài liệu (GLOBAL, không tenant) cho fallback keyword
 *   + payload hiển thị nguồn. Vector sống ở Qdrant (point id = help_chunks.id).
 * - `support_requests`: câu hỏi gửi CSKH (theo tenant), trạng thái chờ → đã trả lời.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64)->default('rag_chunks'); // nguồn file
            $table->string('ref_key', 191)->unique();            // khoá idempotent mỗi chunk
            $table->string('title', 255);
            $table->string('module', 64)->nullable();
            $table->string('screen', 191)->nullable();
            $table->text('question')->nullable();
            $table->longText('answer');
            $table->json('keywords')->nullable();
            $table->longText('chunk_text');                      // text dùng để embed + keyword match
            $table->string('embedding_model', 128)->nullable();  // null ⇒ chưa có vector (chỉ keyword)
            $table->unsignedInteger('token_count')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();
            $table->index('module');
        });

        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->text('question');
            $table->string('status', 24)->default('pending'); // pending|answered|closed
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
        Schema::dropIfExists('help_chunks');
    }
};
