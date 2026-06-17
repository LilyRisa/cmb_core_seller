<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_bad_reports — cache báo cáo "bom hàng" tra từ Pancake POS khi tạo đơn
 * thủ công (SPEC 0038). Tách khỏi `customers` để chạy được CẢ khi khách chưa
 * tồn tại (đơn thủ công khách mới), đồng thời làm lớp cache (TTL 24h). Đối chiếu
 * qua `phone_hash` = sha256(normalized) — KHÔNG lưu số điện thoại thô (PII).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_bad_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->char('phone_hash', 64);
            $table->unsignedInteger('order_fail')->default(0);
            $table->unsignedInteger('order_success')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->json('warnings')->nullable();           // [{reason, reported_at}]
            $table->boolean('has_data')->default(false);    // true nếu Pancake trả khớp số (phân biệt "đã tra, sạch" vs chưa tra)
            $table->timestamp('synced_at');                 // mốc cache
            $table->timestamps();

            $table->unique(['tenant_id', 'phone_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_bad_reports');
    }
};
