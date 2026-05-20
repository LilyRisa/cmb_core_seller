<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `messaging_settings` — cấu hình Messaging cấp TENANT (1 row / tenant). Khác
 * `messaging_account_meta` (per-shop). Lưu lựa chọn AI provider của tenant +
 * giờ vắng mặt + fallback template.
 *
 * Không dùng được tenant-settings chung vì repo chưa có cơ chế đó — bảng riêng
 * do Messaging sở hữu (self-contained, không couple Tenancy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->primary();
            $table->string('ai_provider_code', 32)->nullable();   // ∈ ai_providers.is_active
            $table->boolean('ai_enabled')->default(false);
            // auto_mode (S7): AI tự gửi reply (qua guardrail intent), KHÔNG cần NV duyệt.
            // Opt-in; mặc định false (suggest-only — SPEC §11 Q2).
            $table->boolean('auto_mode')->default(false);
            $table->json('away_hours')->nullable();               // {window:'22:00-08:00', tz, days}
            $table->unsignedBigInteger('fallback_template_id')->nullable();
            $table->json('settings')->nullable();                 // mở rộng tương lai
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_settings');
    }
};
