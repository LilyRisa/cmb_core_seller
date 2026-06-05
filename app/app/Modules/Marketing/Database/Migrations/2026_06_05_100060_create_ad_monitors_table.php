<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_monitors — auto-rules per campaign/adset: raise budget when cost-per-result
 * is cheap, pause when it's too expensive. Evaluated in the background.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->string('target_level');          // campaign|adset
            $table->string('target_external_id')->index();
            $table->boolean('enabled')->default(true);
            $table->boolean('increase_enabled')->default(false);
            $table->unsignedBigInteger('increase_below')->nullable(); // VND cost-per-result threshold
            $table->unsignedSmallInteger('increase_step_pct')->default(20);
            $table->unsignedBigInteger('max_daily_budget')->nullable(); // VND cap
            $table->boolean('pause_enabled')->default(false);
            $table->unsignedBigInteger('pause_above')->nullable();      // VND cost-per-result threshold
            $table->unsignedInteger('min_results')->default(1);
            $table->timestamp('last_evaluated_at')->nullable();
            $table->string('last_action')->nullable();
            $table->timestamp('last_action_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->unique(['ad_account_id', 'target_level', 'target_external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_monitors');
    }
};
