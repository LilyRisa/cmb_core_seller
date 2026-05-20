<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đơn manual ghi nhận kho gửi → in phiếu giao hàng đọc sender_* từ
 * `warehouses.address`. Đơn cũ giữ NULL → resolver fallback default warehouse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('tenant_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
