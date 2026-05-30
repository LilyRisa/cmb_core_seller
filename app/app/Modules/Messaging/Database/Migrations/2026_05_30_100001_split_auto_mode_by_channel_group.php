<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tách `auto_mode` (AI tự gửi tất cả) thành 2 nhóm kênh (ADR-0022):
 *  - auto_mode_marketplace: sàn TMĐT (tiktok/shopee/lazada/manual)
 *  - auto_mode_facebook:    facebook_page
 *
 * `ai_enabled` + `ai_provider_code` GIỮ chung cấp tenant — chỉ công tắc "tự gửi
 * tất cả" mới tách (nó là thứ xung đột với flow `inbox_any`). Cột `auto_mode` cũ
 * GIỮ lại (deprecated) + backfill sang 2 cột mới để không phá dữ liệu/DB dùng chung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_settings', function (Blueprint $table) {
            $table->boolean('auto_mode_marketplace')->default(false)->after('auto_mode');
            $table->boolean('auto_mode_facebook')->default(false)->after('auto_mode_marketplace');
        });

        // Backfill: giữ nguyên hành vi hiện tại — auto_mode cũ true ⇒ cả 2 nhóm true.
        DB::table('messaging_settings')
            ->where('auto_mode', true)
            ->update(['auto_mode_marketplace' => true, 'auto_mode_facebook' => true]);
    }

    public function down(): void
    {
        Schema::table('messaging_settings', function (Blueprint $table) {
            $table->dropColumn(['auto_mode_marketplace', 'auto_mode_facebook']);
        });
    }
};
