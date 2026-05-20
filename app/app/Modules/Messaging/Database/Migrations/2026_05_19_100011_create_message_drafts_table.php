<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `message_drafts` — AI suggestion chờ NV duyệt. Tự xoá sau 1h qua
 * `PruneAiSuggestionDrafts` (S5). NV bấm "Gửi" ⇒ `accept` → tạo message thật.
 *
 * SPEC-0024 §5.11.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('conversation_id');
            $table->foreignId('ai_run_id')->nullable();
            $table->text('draft_text');
            $table->json('suggested_attachments')->nullable();
            $table->string('status', 16)->default('pending');          // pending|accepted|rejected|expired
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable();
            $table->foreignId('accepted_message_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['tenant_id', 'conversation_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_drafts');
    }
};
