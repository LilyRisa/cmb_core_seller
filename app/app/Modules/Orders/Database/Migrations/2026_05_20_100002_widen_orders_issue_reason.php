<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders.issue_reason: varchar(255) → text — lý do cảnh báo của đơn (vd lỗi gọi API sàn khi "Chuẩn bị hàng")
 * có thể dài hơn 255 ký tự, gây SQLSTATE[22001] khi update. Đổi sang TEXT để không bao giờ tràn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('issue_reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('issue_reason')->nullable()->change();
        });
    }
};
