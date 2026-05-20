<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0019: `channel_accounts` mở rộng cho social providers (Facebook Page,
 * Zalo OA, ...). Thêm cờ `messaging_enabled` để toggle messaging per shop —
 * bật khi provider có messaging capability HOẶC user explicit enable.
 *
 * Default false ⇒ shop hiện có không tự nhiên bật → giữ behaviour cũ.
 *
 * Module Messaging KHÔNG sửa schema này trực tiếp — đặt migration ở đây
 * (Messaging module) vì cột chỉ phục vụ messaging. Channels có thể đọc qua
 * `MessagingEnablementContract` (sau khi wire).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->boolean('messaging_enabled')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->dropColumn('messaging_enabled');
        });
    }
};
