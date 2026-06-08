<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0036 — Thông báo trong ứng dụng (in-app notifications).
 *
 * Bảng tên `app_notifications` (KHÔNG dùng `notifications` để tránh đụng bảng mặc định
 * của Laravel database notifications — User dùng trait Notifiable). 1 dòng / 1 user nhận
 * (read_at per-user). `dedup_key` chống tạo trùng khi event lặp (vd reconnect mỗi 30').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->index();              // người nhận
            $table->string('type', 48);                          // hằng số NotificationType
            $table->string('level', 12)->default('info');        // info|warning|critical
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('action_url', 512)->nullable();       // deep-link FE (vd /orders/123)
            $table->json('data')->nullable();                    // id thực thể để FE render
            $table->string('dedup_key', 160)->nullable();        // chống trùng theo entity
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Badge + sắp xếp danh sách theo user trong tenant hiện tại.
            $table->index(['tenant_id', 'user_id', 'id']);
            // Đếm chưa đọc nhanh.
            $table->index(['tenant_id', 'user_id', 'read_at']);
            // Dedup: tồn tại bản chưa đọc cùng key cho user?
            $table->index(['tenant_id', 'user_id', 'dedup_key', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
