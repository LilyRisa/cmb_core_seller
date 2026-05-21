<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SPEC 2026-05-21: comment-sync state columns — tách biệt khỏi message sync_status. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->string('comment_sync_status', 16)->default('idle')->after('last_synced_at'); // idle|running|done|failed
            $table->timestamp('comment_synced_at')->nullable()->after('comment_sync_status');
            $table->text('comment_sync_error')->nullable()->after('comment_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->dropColumn(['comment_sync_status', 'comment_synced_at', 'comment_sync_error']);
        });
    }
};
