<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `ai_knowledge_documents` + `ai_knowledge_chunks` — RAG cho AI assistant.
 *
 * S1 chỉ tạo schema; S6 sẽ implement embedding + retrieval. `embedding` lưu
 * dạng JSON ở S1 (Postgres pgvector setup ở S6 — cần extension), MySQL/SQLite
 * test môi trường vẫn dùng JSON.
 *
 * SPEC-0024 §5.8 §5.9.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('title');
            $table->string('source', 16);                              // upload|url|inline
            $table->string('storage_path', 512)->nullable();
            $table->string('url', 1024)->nullable();
            $table->text('inline_text')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->string('embedding_provider_code', 32)->nullable();
            $table->string('embedding_model', 64)->nullable();
            $table->unsignedSmallInteger('embedding_version')->default(1);
            $table->timestamp('indexed_at')->nullable();
            $table->string('status', 16)->default('pending');          // pending|ready|failed
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('document_id');
            $table->unsignedInteger('chunk_index');
            $table->text('chunk_text');
            // S1: lưu JSON. S6 sẽ ALTER trên Postgres pgvector (vector(1536))
            // cho HNSW index; MySQL/SQLite test vẫn dùng JSON.
            $table->json('embedding')->nullable();
            $table->unsignedInteger('token_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_chunks');
        Schema::dropIfExists('ai_knowledge_documents');
    }
};
