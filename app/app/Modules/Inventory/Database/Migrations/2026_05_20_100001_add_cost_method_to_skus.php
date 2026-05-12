<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-SKU cost basis for profit reporting (SPEC 0012):
 *  - `cost_method` = 'average' (giá vốn bình quân gia quyền, mặc định) | 'latest' (lấy đơn giá lô nhập kho gần nhất)
 *  - `last_receipt_cost` = đơn giá của lần nhập kho gần nhất (cập nhật khi confirm phiếu nhập kho)
 * `cost_price` vẫn là giá vốn tham khảo / bình quân toàn-công-ty (cập nhật cùng lúc). "Giá vốn hiệu lực"
 * = cost_method='latest' ? (last_receipt_cost ?: cost_price) : cost_price — dùng tính COGS của đơn hàng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->string('cost_method', 16)->default('average')->after('cost_price');
            $table->bigInteger('last_receipt_cost')->nullable()->after('cost_method');
        });
    }

    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn(['cost_method', 'last_receipt_cost']);
        });
    }
};
