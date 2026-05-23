<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fallback URL CDN avatar của page (giống buyer_avatar_url của conversation) — hiển
 * thị ngay khi relay vào object storage chưa xong / chưa cấu hình (vd R2 thiếu env).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->string('page_avatar_url', 512)->nullable()->after('page_avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->dropColumn('page_avatar_url');
        });
    }
};
