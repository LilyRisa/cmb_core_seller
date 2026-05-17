<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 2026-05-17 — bảng cấu hình hệ thống do super-admin quản lý qua UI
 * `/admin/settings`. Key phải thuộc whitelist `SystemSettingsCatalog` (38 key
 * trong 4 nhóm). Khi đọc qua `system_setting()`, các key ngoài whitelist trả
 * default. Secret encrypt bằng `Crypt::encryptString` (AES-256-CBC dựa
 * APP_KEY).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->text('value')->nullable();
            $table->string('type', 16);
            $table->string('group', 32);
            $table->boolean('is_secret')->default(false);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->timestamps();
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
