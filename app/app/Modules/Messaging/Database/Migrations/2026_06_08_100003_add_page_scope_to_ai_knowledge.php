<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0035 — per-page scoping cho tài liệu AI training (knowledge base).
 * 1 tài liệu gán NHIỀU page (pivot) + cờ `applies_all_pages`. Tài liệu cũ ⇒ áp mọi page.
 * Retrieval lọc theo document_id đã lọc nên chunk KHÔNG cần pivot riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_knowledge_documents', function (Blueprint $table) {
            $table->boolean('applies_all_pages')->default(false)->after('status');
        });

        Schema::create('ai_knowledge_document_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ai_knowledge_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['ai_knowledge_document_id', 'channel_account_id'], 'akd_page_unique');
            $table->index('channel_account_id');
        });

        DB::table('ai_knowledge_documents')->update(['applies_all_pages' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_document_page');
        Schema::table('ai_knowledge_documents', function (Blueprint $table) {
            $table->dropColumn('applies_all_pages');
        });
    }
};
