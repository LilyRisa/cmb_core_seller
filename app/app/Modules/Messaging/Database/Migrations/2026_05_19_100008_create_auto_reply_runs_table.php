<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `auto_reply_runs` — idempotency + cooldown lookup cho engine.
 *
 * UNIQUE `(rule_id, conversation_id, window_key)` ⇒ insert fail = skip silently
 * (rule chạy 2 lần cùng window = 1 fire).
 *
 * `window_key`: `'YYYY-MM-DD-HH'` cho schedule | `"order:{id}:status:{s}"` cho
 * order_status | `"away:{conv_id}:{interval_start_ts}"` cho away.
 *
 * SPEC-0024 §5.7. Phase sau partition theo `fired_at` tháng + prune > 90 ngày.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_reply_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('rule_id');
            $table->foreignId('conversation_id');
            $table->string('window_key', 128);
            $table->timestamp('fired_at')->useCurrent();
            $table->foreignId('message_id')->nullable();
            $table->string('status', 24)->default('fired');     // fired|skipped_cooldown|skipped_filter|failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['rule_id', 'conversation_id', 'window_key'], 'auto_reply_runs_unique');
            $table->index(['tenant_id', 'fired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_runs');
    }
};
