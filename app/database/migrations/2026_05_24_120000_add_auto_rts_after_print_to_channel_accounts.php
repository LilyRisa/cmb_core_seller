<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-shop Lazada: tự đẩy /order/rts sau khi in tem ("sẵn sàng giao luôn").
 * Default false ⇒ shop hiện có giữ luồng 3 bước cũ. Chỉ dùng cho provider lazada
 * (UI chỉ hiện với Lazada; controller chặn provider khác).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->boolean('auto_rts_after_print')->default(false)->after('messaging_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->dropColumn('auto_rts_after_print');
        });
    }
};
