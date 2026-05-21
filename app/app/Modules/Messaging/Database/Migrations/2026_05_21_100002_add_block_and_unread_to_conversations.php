<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SPEC 2026-05-21: chặn người dùng (mức ứng dụng) + đánh dấu chưa đọc + avatar relay (slice 1). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('status');
            $table->foreignId('blocked_by_user_id')->nullable()->after('blocked_at');
            $table->boolean('manually_unread')->default(false)->after('unread_count');
            $table->string('buyer_avatar_path', 512)->nullable()->after('buyer_avatar_url');
            $table->index(['tenant_id', 'blocked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_tenant_id_blocked_at_index');
            $table->dropColumn(['blocked_at', 'blocked_by_user_id', 'manually_unread', 'buyer_avatar_path']);
        });
    }
};
