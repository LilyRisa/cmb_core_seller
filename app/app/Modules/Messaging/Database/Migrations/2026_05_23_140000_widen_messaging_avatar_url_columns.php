<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * URL CDN của Facebook (scontent... / lookaside) dài ~700–1000+ ký tự (kèm hàng loạt
 * tham số _nc_*, oh, oe, _nc_tpa...) ⇒ vượt varchar(512) gây lỗi 22001 "value too long"
 * khi lưu page_avatar_url / buyer_avatar_url. Lỗi này nổ ở bước avatar page (trước
 * try/catch của backfill) ⇒ VỠ TOÀN BỘ đồng bộ. Nới các cột URL sang TEXT (không giới hạn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->text('page_avatar_url')->nullable()->change();
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->text('buyer_avatar_url')->nullable()->change();
        });
        Schema::table('message_attachments', function (Blueprint $table) {
            $table->text('external_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->string('page_avatar_url', 512)->nullable()->change();
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('buyer_avatar_url', 512)->nullable()->change();
        });
        Schema::table('message_attachments', function (Blueprint $table) {
            $table->string('external_url', 1024)->nullable()->change();
        });
    }
};
