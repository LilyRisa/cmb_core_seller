<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm `adapter_config` (JSON) cho adapter `custom_http` (SPEC-0026): lưu
 * method/headers/request_template/response_path/usage để super-admin khai báo
 * provider HTTP bất kỳ ngay trong /admin/ai-providers (không cần connector PHP mới).
 *
 * Các adapter khác (anthropic/openai_compatible/manual) để `null` — không đụng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->json('adapter_config')->nullable()->after('pricing');
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropColumn('adapter_config');
        });
    }
};
