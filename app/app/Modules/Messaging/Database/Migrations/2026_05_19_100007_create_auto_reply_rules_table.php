<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `auto_reply_rules` — 4 trigger: schedule|order_status|away_no_response|first_message.
 * S1 chỉ tạo schema; S5 (sau Phase 6.5 done) sẽ wire vào AutomationRule engine
 * và implement triggers.
 *
 * SPEC-0024 §5.6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name');
            $table->string('trigger', 32);                  // schedule|order_status|away_no_response|first_message
            $table->json('trigger_config');                 // shape phụ thuộc trigger
            $table->json('filter')->nullable();             // {providers, customer_tags, keywords}
            $table->json('action');                         // {kind:template|raw|ai_reply, template_id?, raw_text?, ai_prompt_extra?}
            $table->unsignedInteger('cooldown_seconds')->default(3600);
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'trigger', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_rules');
    }
};
