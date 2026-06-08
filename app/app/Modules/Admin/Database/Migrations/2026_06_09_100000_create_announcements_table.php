<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0037 — `announcements`: popup thông báo toàn hệ thống do super-admin tạo
 * (fix bug, tạm dừng dịch vụ…). KHÔNG tenant-scoped (admin global). `body_html` đã
 * được sanitize allowlist trước khi lưu. Ảnh/video trong nội dung trỏ R2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->longText('body_html');                  // HTML đã sanitize
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('starts_at')->nullable();      // cửa sổ chiếu (tuỳ chọn)
            $table->timestamp('ends_at')->nullable();
            $table->string('dismiss_label', 40)->default('Đã hiểu');
            $table->foreignId('created_by_user_id');         // admin_user id
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']); // truy vấn active nhanh
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
