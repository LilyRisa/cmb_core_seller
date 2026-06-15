<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `price` là giá HIỆN TẠI (có thể đã giảm) — thêm `original_price` (giá gốc chưa giảm)
 * để chiến dịch giảm giá lấy đúng giá gốc làm base, không bị "giảm trên giá đã giảm".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_listings', function (Blueprint $table) {
            $table->unsignedBigInteger('original_price')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('channel_listings', function (Blueprint $table) {
            $table->dropColumn('original_price');
        });
    }
};
