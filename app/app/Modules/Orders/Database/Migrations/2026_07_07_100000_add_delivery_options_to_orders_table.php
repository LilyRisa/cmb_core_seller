<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // delivery_note, delivery_fee_payer & delivery_inspection đã bị bỏ (reconciliation 2026-07-07):
            // phí ship là nội bộ (đã gộp vào COD đẩy ĐVVC) — không map ai-trả-phí lên carrier; "chế độ xem
            // hàng" chưa từng được yêu cầu — bỏ hẳn. "Ghi chú giao hàng" dùng lại `meta.print_note`.
            // Chỉ cột dưới là mới: khoản thu khi giao thất bại (failed_delivery_collect capability).
            $table->unsignedInteger('failed_collect_amount')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['failed_collect_amount']);
        });
    }
};
