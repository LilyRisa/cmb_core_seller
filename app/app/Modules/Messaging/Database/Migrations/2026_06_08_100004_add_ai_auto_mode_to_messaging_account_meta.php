<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0035 — AI auto-mode (AI tự trả lời) theo TỪNG PAGE.
 *
 * Trước đây auto-mode lưu ở `messaging_settings` (1 row/tenant) tách theo NHÓM
 * (auto_mode_facebook / auto_mode_marketplace). Nay thêm `ai_auto_mode` per-page ở
 * `messaging_account_meta`. Backfill: mỗi page kế thừa cờ nhóm hiện tại của tenant
 * (FB page ← auto_mode_facebook; còn lại ← auto_mode_marketplace) để không mất hành vi.
 * Cờ nhóm-tenant GIỮ lại làm fallback đọc trong giai đoạn chuyển tiếp (dọn ở spec sau).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->boolean('ai_auto_mode')->default(false)->after('ai_enabled');
        });

        $rows = DB::table('messaging_account_meta as m')
            ->join('channel_accounts as ca', 'ca.id', '=', 'm.channel_account_id')
            ->leftJoin('messaging_settings as s', 's.tenant_id', '=', 'm.tenant_id')
            ->select('m.channel_account_id', 'ca.provider', 's.auto_mode_facebook', 's.auto_mode_marketplace')
            ->get();

        foreach ($rows as $r) {
            $isFacebook = str_contains((string) $r->provider, 'facebook');
            $auto = $isFacebook
                ? (bool) ($r->auto_mode_facebook ?? false)
                : (bool) ($r->auto_mode_marketplace ?? false);

            DB::table('messaging_account_meta')
                ->where('channel_account_id', $r->channel_account_id)
                ->update(['ai_auto_mode' => $auto]);
        }
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->dropColumn('ai_auto_mode');
        });
    }
};
