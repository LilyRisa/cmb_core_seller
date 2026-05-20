<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `ai_assistant_runs` — audit + cost tracking. Mỗi call LLM ghi 1 row.
 * Super-admin `/admin/messaging/ai-usage` đọc bảng này để charge per-tenant.
 *
 * S1 bảng phẳng; phase sau partition theo `created_at` tháng + prune > 365d.
 *
 * SPEC-0024 §5.10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assistant_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('conversation_id')->nullable();
            $table->foreignId('message_id')->nullable();
            $table->string('provider_code', 32);
            $table->string('model', 64)->nullable();
            $table->string('mode', 16);                                // suggest|auto|intent|rag
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->bigInteger('cost_micro_vnd')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 24);                              // success|error|timeout|blocked_by_guardrail
            $table->text('error')->nullable();
            $table->json('meta')->nullable();                          // {redacted_count, intent, ...}
            $table->foreignId('created_by')->nullable();               // NULL = system (auto)
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'provider_code', 'created_at']);
            $table->index(['tenant_id', 'mode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_runs');
    }
};
