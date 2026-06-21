<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giá giảm (special price) đang chạy của SKU trên sàn — Lazada không có "đối tượng chương trình" mà set
 * SalePrice theo từng SKU; lưu để tạo chiến dịch giảm giá phát hiện SKU đã có khuyến mãi (tô xám + hiện giá).
 * NULL = không có giảm giá đang chạy. Đồng bộ từ sàn ở FetchChannelListings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_listings', function (Blueprint $table) {
            $table->integer('special_price')->nullable()->after('original_price');
        });
    }

    public function down(): void
    {
        Schema::table('channel_listings', function (Blueprint $table) {
            $table->dropColumn('special_price');
        });
    }
};
