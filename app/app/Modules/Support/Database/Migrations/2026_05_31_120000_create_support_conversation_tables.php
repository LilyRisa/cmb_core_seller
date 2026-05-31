<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CSKH chuyển từ mô hình "1 câu hỏi – 1 trả lời" (`support_requests`) sang HỘI THOẠI
 * nhiều tin (SPEC-0028, cập nhật 2026-05-31). Feature chưa chạy live ⇒ bỏ bảng cũ.
 *
 * - `support_conversations`: 1 đoạn hội thoại CSKH theo tenant; đóng/mở theo `status`.
 *   `user_unread_count` = số tin CSKH chưa đọc phía user (driving badge widget).
 * - `support_messages`: tin trong hội thoại — `sender` user|cskh, `type` text|system
 *   (system = thông báo "đã đóng"…).
 * - `support_message_attachments`: file/ảnh/video đính kèm 1 tin (mô phỏng
 *   `message_attachments` của Messaging, đơn giản hoá).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('support_requests');

        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->nullable()->index();       // người mở hội thoại
            $table->string('status', 16)->default('open');           // open|closed
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_sender', 8)->nullable();            // user|cskh — biết đang chờ ai
            $table->unsignedInteger('user_unread_count')->default(0); // tin CSKH user chưa đọc
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable();              // admin_user_id đóng
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'last_message_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('support_conversation_id')->index();
            $table->string('sender', 8);                             // user|cskh
            $table->string('type', 12)->default('text');            // text|system
            $table->foreignId('user_id')->nullable();               // nếu sender=user
            $table->foreignId('admin_id')->nullable();              // nếu sender=cskh
            $table->text('body')->nullable();                       // null nếu chỉ có đính kèm
            $table->unsignedSmallInteger('attachments_count')->default(0);
            $table->timestamps();
            $table->index(['support_conversation_id', 'id']);       // sắp xếp thread
        });

        Schema::create('support_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('support_message_id')->index();
            $table->string('kind', 16);                             // image|video|file
            $table->string('mime', 128);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('storage_path', 512)->nullable();
            $table->string('checksum', 64)->nullable();             // sha256 hex
            $table->string('filename', 255)->nullable();
            $table->string('status', 16)->default('stored');        // stored|failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_message_attachments');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');

        // Khôi phục bảng cũ (đối xứng với up) — mô hình "1 câu hỏi – 1 trả lời".
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->text('question');
            $table->string('status', 24)->default('pending');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }
};
