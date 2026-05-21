<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tách `code` (slug instance tự do) khỏi `adapter` (loại API connector).
 * adapter: anthropic | openai_compatible | manual. Cho phép NHIỀU instance cùng
 * adapter (deepseek/qwen/openrouter đều openai_compatible, khác base_url/key/model).
 *
 * `adapter` để nullable ở tầng DB (sqlite test-friendly); 'required' được ép ở
 * tầng validation controller. Backfill rows cũ theo code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->string('adapter', 24)->nullable()->index()->after('code');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
            $table->string('notes')->nullable()->after('sort_order');
        });

        DB::table('ai_providers')->where('code', 'claude')->update(['adapter' => 'anthropic']);
        DB::table('ai_providers')->where('code', 'openai')->update(['adapter' => 'openai_compatible']);
        DB::table('ai_providers')->where('code', 'manual')->update(['adapter' => 'manual']);
        DB::table('ai_providers')->whereNull('adapter')->update(['adapter' => 'openai_compatible']);
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropIndex(['adapter']);
            $table->dropColumn(['adapter', 'sort_order', 'notes']);
        });
    }
};
