<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SPEC 2026-05-21: sync-state + page avatar cho backfill Facebook (slice 1). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->string('page_avatar_path', 512)->nullable()->after('settings');
            $table->timestamp('page_avatar_synced_at')->nullable()->after('page_avatar_path');
            $table->string('sync_status', 16)->default('idle')->after('page_avatar_synced_at'); // idle|queued|running|done|failed
            $table->unsignedInteger('sync_total_conversations')->nullable()->after('sync_status');
            $table->unsignedInteger('sync_done_conversations')->default(0)->after('sync_total_conversations');
            $table->unsignedInteger('sync_message_count')->default(0)->after('sync_done_conversations');
            $table->text('sync_cursor')->nullable()->after('sync_message_count');
            $table->timestamp('sync_started_at')->nullable()->after('sync_cursor');
            $table->timestamp('sync_finished_at')->nullable()->after('sync_started_at');
            $table->text('sync_error')->nullable()->after('sync_finished_at');
            $table->timestamp('last_synced_at')->nullable()->after('sync_error');
        });
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->dropColumn([
                'page_avatar_path', 'page_avatar_synced_at', 'sync_status',
                'sync_total_conversations', 'sync_done_conversations', 'sync_message_count',
                'sync_cursor', 'sync_started_at', 'sync_finished_at', 'sync_error', 'last_synced_at',
            ]);
        });
    }
};
