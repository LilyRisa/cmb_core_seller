<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * marketing_ai_providers — AI provider DEDICATED to marketing data analysis
 * (forecast/strategy). Fully separate from messaging `ai_providers` so the two
 * AI flows never share config (SPEC 2026-06-04, "không đụng luồng khác").
 * Super-admin managed via a dedicated admin screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_ai_providers', function (Blueprint $table) {
            $table->string('code', 32)->primary();              // 'forecast-claude' | ...
            $table->string('display_name')->nullable();
            $table->string('adapter', 32);                      // anthropic | openai_compatible | manual
            $table->text('api_key')->nullable();                // encrypted
            $table->string('base_url')->nullable();
            $table->string('default_model', 64)->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_ai_providers');
    }
};
