<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 2/2 (design 2026-07-14 §2). CHỈ chạy sau khi `php artisan webhooks:backfill-dedupe-key`
 * (Task 5) đã dọn sạch row trùng — migration TỰ KIỂM TRA còn trùng thì ném exception rõ ràng, KHÔNG
 * âm thầm bỏ qua hay thêm constraint nửa vời.
 *
 * Giới hạn đã biết: unique index chuẩn SQL coi NULL khác NULL (2 row cùng NULL không vi phạm unique).
 * `external_id` và/hoặc `external_shop_id` có thể NULL tuỳ provider/event — các row đó KHÔNG được bảo vệ
 * bởi constraint này, vẫn dựa hoàn toàn vào fast-path `exists()` (racy) như trước feature này, chỉ cho
 * riêng tập con đó. Không phải regression, nhưng promise "dedupe atomic, không còn race window" chỉ đúng
 * với các event có đủ external_id + external_shop_id. Cố ý không COALESCE NULL→'' lúc ghi (out of scope,
 * để hardening sau).
 */
return new class extends Migration
{
    public function up(): void
    {
        $dup = DB::table('webhook_events')
            ->select('provider', 'event_type', 'external_id', 'external_shop_id', 'dedupe_status_key')
            ->groupBy('provider', 'event_type', 'external_id', 'external_shop_id', 'dedupe_status_key')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->first();

        if ($dup !== null) {
            throw new RuntimeException(
                'webhook_events còn row trùng theo khoá dedupe — chạy `php artisan webhooks:backfill-dedupe-key`'
                .' trước rồi migrate lại (design 2026-07-14 §2, giai đoạn 2).'
            );
        }

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->unique(
                ['provider', 'event_type', 'external_id', 'external_shop_id', 'dedupe_status_key'],
                'webhook_events_dedupe_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique('webhook_events_dedupe_unique');
        });
    }
};
