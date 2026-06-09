<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liên kết comment → DM theo bài viết (SPEC 2026-06-09 flow theo bài viết cho inbox).
 *
 * Khi gửi tin riêng cho 1 bình luận, Facebook trả PSID người nhận (recipient_id) —
 * lúc đó đã biết fb_post_id từ hội thoại comment. Lưu map (page, psid) → fb_post_id
 * để khi khách trả lời trong Messenger, hội thoại DM được gắn đúng bài viết nguồn
 * (CommentDmLinker::stampInbound, first-touch). Mới nhất thắng (upsert theo unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_comment_dm_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id')->index();
            $table->string('psid', 191);             // PSID người dùng trong Messenger (page-scoped)
            $table->string('fb_post_id', 191);       // "{page_id}_{post_id}"
            $table->string('fb_comment_id', 191)->nullable();
            $table->timestamp('linked_at');
            $table->timestamps();

            $table->unique(['channel_account_id', 'psid']); // mới nhất thắng
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_comment_dm_links');
    }
};
