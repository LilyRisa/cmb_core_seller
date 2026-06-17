<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_reports — báo cáo "bom hàng" do người bán tự tạo từ đơn THỦ CÔNG bị
 * hoàn/thất bại (SPEC 0038 v2). Đối chiếu khách qua `phone_hash`. Mỗi đơn chỉ
 * tạo 1 report (`unique(order_id)`). Đây là nguồn ưu tiên; Pancake chỉ bù đắp
 * khi không có report nội bộ lẫn cache Pancake.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->char('phone_hash', 64);
            $table->unsignedBigInteger('order_id')->unique();   // mỗi đơn chỉ báo 1 lần
            $table->string('order_number')->nullable();
            $table->string('reason', 255);
            $table->foreignId('reported_by_user_id')->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->index(['tenant_id', 'phone_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_reports');
    }
};
