<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 1/2 (design 2026-07-14-manual-order-phone-hash-and-webhook-dedupe §2) — chỉ thêm cột,
 * KHÔNG thêm unique constraint (dữ liệu hiện tại có thể đã có row trùng do race cũ — xem giai đoạn 2
 * migration `2026_07_14_100001_add_dedupe_unique_to_webhook_events_table.php`, PHẢI chạy
 * `php artisan webhooks:backfill-dedupe-key` giữa 2 giai đoạn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('dedupe_status_key')->nullable()->after('order_raw_status');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn('dedupe_status_key');
        });
    }
};
