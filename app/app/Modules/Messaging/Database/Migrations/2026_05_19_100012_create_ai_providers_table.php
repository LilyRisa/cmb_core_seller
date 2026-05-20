<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `ai_providers` — cấu hình LLM provider do SUPER-ADMIN quản (KHÔNG tenant-scoped;
 * catalog chung như `plans`). Tenant chỉ chọn 1 code `is_active` trong
 * `/settings/messaging`.
 *
 * Vì sao bảng riêng thay vì `system_settings` (ADR-0018 bản Proposed):
 * `SystemSettingsCatalog` là allowlist key TĨNH (exact-match) — không nhận key
 * động `ai_providers.<code>.*`, nên `system_setting()` luôn trả default ⇒
 * registry không bao giờ thấy provider active (lỗi tiềm ẩn S1). Bảng riêng:
 *   - mô hình hoá đúng (record có cấu trúc: key mã hoá, pricing json)
 *   - không phá hợp đồng "single source of truth" của Settings module
 *   - registry đọc `is_active` trực tiếp, test được không cần system_settings
 *
 * `capabilities` KHÔNG lưu DB — đọc từ connector class
 * (`$registry->for($code)->capabilities()`) để super-admin không thể "claim"
 * capability mà class không implement.
 *
 * `api_key` encrypted-at-rest (model cast `encrypted`). Không bao giờ lộ ra tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->string('code', 32)->primary();          // 'claude'|'openai'|'gemini'|'local_llm'|'manual'
            $table->string('display_name')->nullable();      // override tên hiển thị; null = connector->displayName()
            $table->text('api_key')->nullable();             // encrypted
            $table->string('base_url')->nullable();          // cho local_llm / self-host
            $table->string('default_model', 64)->nullable();
            $table->json('pricing')->nullable();             // [{kind,unit,micro_vnd}]
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
