<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `shipments.label_fetch_next_retry_at` — thời điểm dự kiến của *lần retry kế* của
 * `FetchChannelLabel` job (Lazada 3PL render AWB async 5–30s+ sau /order/rts).
 *
 *  - Khi `ShipmentService::fetchAndStoreChannelLabel` exhaust sync retry & dispatch `FetchChannelLabel`
 *    với delay ⇒ set = `now() + delay` (lần retry kế).
 *  - Job `FetchChannelLabel::handle()` lấy được tem ⇒ clear (cùng commit với `label_path`).
 *  - Job exhausted (5 lần) ⇒ clear + Laravel queue tự cờ `has_issue` qua exception bubble.
 *
 * Phân loại sub-tab "Tình trạng phiếu giao hàng" (SPEC 0013 §3 — cập nhật 2026-05-14):
 *   - "Có thể in"          : `label_path IS NOT NULL`
 *   - "Đang tải lại"       : `label_path IS NULL AND label_fetch_next_retry_at > NOW()`   (job còn trong queue)
 *   - "Nhận phiếu giao hàng": `label_path IS NULL AND (label_fetch_next_retry_at IS NULL OR <= NOW())` (user retry)
 *
 * Index nhỏ chỉ cho `label_fetch_next_retry_at` (filter `> NOW()` chạy thường xuyên ở `stats.by_slip`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipments') || Schema::hasColumn('shipments', 'label_fetch_next_retry_at')) {
            return;
        }
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('label_fetch_next_retry_at')->nullable()->after('label_path');
            $table->index('label_fetch_next_retry_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipments') || ! Schema::hasColumn('shipments', 'label_fetch_next_retry_at')) {
            return;
        }
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['label_fetch_next_retry_at']);
            $table->dropColumn('label_fetch_next_retry_at');
        });
    }
};
