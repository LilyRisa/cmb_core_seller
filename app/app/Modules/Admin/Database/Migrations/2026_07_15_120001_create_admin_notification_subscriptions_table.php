<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 2026-07-15 — loại thông báo mỗi email admin đã bật. Bảng TÁCH RIÊNG, không JSON,
 * không dùng chung bảng nào khác (quyết định người dùng).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_notification_recipient_id')
                ->constrained('admin_notification_recipients')->cascadeOnDelete();
            $table->string('notification_type');
            $table->timestamp('created_at')->nullable();

            // Tên tự sinh ("admin_notification_subscriptions_admin_notification_recipient_id_notification_type_unique",
            // 89 ký tự) và tên constraint FK tự sinh của cột trên (72 ký tự) CÙNG chung tiền tố quá dài —
            // Postgres cắt còn 63 ký tự (NAMEDATALEN) khiến cả 2 trùng tên ⇒ "constraint already exists"
            // (migration fail, transaction rollback, bảng biến mất). Đặt tên ngắn tường minh để tránh trùng.
            $table->unique(['admin_notification_recipient_id', 'notification_type'], 'ans_recipient_notification_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_subscriptions');
    }
};
