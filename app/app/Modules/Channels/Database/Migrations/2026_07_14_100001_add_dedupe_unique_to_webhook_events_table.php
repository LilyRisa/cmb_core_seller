<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 2/2 (design 2026-07-14 §2). CHỈ chạy sau khi `php artisan webhooks:backfill-dedupe-key`
 * (Task 5) đã dọn sạch row trùng — migration TỰ KIỂM TRA còn trùng thì ném exception rõ ràng, KHÔNG
 * âm thầm bỏ qua hay thêm constraint nửa vời.
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
