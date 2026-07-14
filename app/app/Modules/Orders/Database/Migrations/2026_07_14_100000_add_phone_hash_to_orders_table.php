<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tra trùng SĐT đơn thủ công nhanh (O(log n) qua index thay vì load hết + so khớp PHP) — design
 * 2026-07-14-manual-order-phone-hash-and-webhook-dedupe. `buyer_phone` cast encrypted nên không
 * query trực tiếp được; hash = sha256(SĐT đã chuẩn hoá), cùng convention `customers.phone_hash`.
 * Additive-only, KHÔNG backfill ở đây — xem lệnh `orders:backfill-phone-hash`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->char('buyer_phone_hash', 64)->nullable()->after('buyer_phone');
            $table->char('recipient_phone_hash', 64)->nullable()->after('shipping_address');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['tenant_id', 'source', 'buyer_phone_hash'], 'orders_buyer_phone_hash_idx');
            $table->index(['tenant_id', 'source', 'recipient_phone_hash'], 'orders_recipient_phone_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_buyer_phone_hash_idx');
            $table->dropIndex('orders_recipient_phone_hash_idx');
            $table->dropColumn(['buyer_phone_hash', 'recipient_phone_hash']);
        });
    }
};
