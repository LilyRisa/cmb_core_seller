<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan C (2026-07-23) — "trang thông báo chung" (ưu đãi/tin chung) do admin soạn, gửi tới
 * tenant cụ thể hoặc tất cả. KHÔNG tenant-scoped (thuộc phạm vi admin global, giống
 * `announcements`). `body_html` đã sanitize allowlist trước khi lưu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_notification_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 160)->unique();
            $table->longText('body_html');
            $table->string('cover_image_url', 512)->nullable();
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_url', 512)->nullable();
            $table->string('audience_type', 16); // all|tenant_ids
            $table->json('audience_tenant_ids')->nullable();
            $table->string('status', 16)->default('draft'); // draft|scheduled|sent
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by_user_id'); // admin_user id
            $table->timestamps();

            $table->index(['status', 'scheduled_at']); // quét lịch gửi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_notification_pages');
    }
};
