<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0039 — `desktop_backgrounds`: thư viện hình nền màn Desktop (giao diện v2) do
 * super-admin quản lý. KHÔNG tenant-scoped (admin global). Ảnh trỏ R2 (storePublic).
 * Người dùng chỉ CHỌN preset (lưu URL trong user_preferences.ui_desktop_bg).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desktop_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('image_url', 1024);
            $table->string('image_path', 1024);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('position')->default(0);
            $table->foreignId('created_by_user_id'); // admin_user id
            $table->timestamps();

            $table->index(['is_active', 'position']); // truy vấn preset active theo thứ tự
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desktop_backgrounds');
    }
};
