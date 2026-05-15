<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.2 — SPEC 0019: Phiếu thu (cash receipt từ khách hàng).
 *
 * Dành cho đơn COD đã thu, đơn chuyển khoản trực tiếp (không qua sàn payout), hoặc gộp nhiều đơn.
 * Mỗi phiếu thu confirm ⇒ JournalService::post(Dr 1111/1121 / Cr 131) — cấn trừ công nợ 131 của
 * khách. Nhiều `applied_orders` jsonb cho phép phân bổ một lần thu nhiều đơn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 24); // 'PT-YYYYMM-NNNN'
            $table->unsignedBigInteger('customer_id')->nullable()->index(); // soft ref
            $table->timestamp('received_at');
            $table->bigInteger('amount'); // VND đồng
            $table->unsignedBigInteger('cash_account_id')->nullable(); // FK cash_accounts (Phase 7.4) — null trước Phase 7.4
            $table->string('payment_method', 24)->default('cash'); // cash | bank | ewallet
            $table->json('applied_orders')->nullable(); // [{order_id, applied_amount}]
            $table->string('memo', 500)->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->string('status', 16)->default('draft'); // draft | confirmed | cancelled
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'customer_id', 'received_at']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receipts');
    }
};
