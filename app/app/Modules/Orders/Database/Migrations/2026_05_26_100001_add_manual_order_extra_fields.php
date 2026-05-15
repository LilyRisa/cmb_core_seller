<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0021 (cont.) — Bổ sung field cho đơn thủ công theo UI tạo đơn mới (taodon.png):
 *   - `prepaid_amount`  → "Tiền chuyển khoản" / đã trả trước (giảm `cod_amount` tương ứng).
 *   - `surcharge`       → "Phụ thu" / phí phát sinh thêm (cộng vào `grand_total`).
 *   - `meta` (jsonb)    → field tự do cho manual order: assignee_user_id, care_user_id,
 *                          marketer_user_id, expected_delivery_date, free_shipping,
 *                          collect_fee_on_return_only, print_note, gender, dob.
 *
 * Mọi field nullable / default 0 ⇒ backwards-compatible: đơn sàn không dùng vẫn OK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('prepaid_amount')->default(0)->after('cod_amount');
            $table->bigInteger('surcharge')->default(0)->after('prepaid_amount');
            $table->json('meta')->nullable()->after('packages');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['prepaid_amount', 'surcharge', 'meta']);
        });
    }
};
