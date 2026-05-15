<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.3 — SPEC 0019: hoá đơn NCC (AP) + phiếu chi.
 *
 *  - `vendor_bills`: lưu hoá đơn của NCC (do shop nhập tay hoặc auto-tạo từ GoodsReceiptConfirmed
 *    nếu rule bật). Khi confirm ⇒ post Dr 1561 (+ Dr 1331 nếu có VAT) / Cr 331 (party=supplier).
 *  - `vendor_payments`: phiếu chi cho NCC. Confirm ⇒ Dr 331 (party=supplier) / Cr 1111/1121.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 24); // 'HDNCC-YYYYMM-NNNN'
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('goods_receipt_id')->nullable();
            $table->string('bill_no', 64)->nullable();      // số hoá đơn NCC
            $table->timestamp('bill_date');
            $table->timestamp('due_date')->nullable();
            $table->bigInteger('subtotal');
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('total');
            $table->string('status', 16)->default('draft'); // draft|recorded|paid|void
            $table->string('memo', 500)->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'supplier_id', 'bill_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'goods_receipt_id']);
        });

        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 24); // 'PC-YYYYMM-NNNN'
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->timestamp('paid_at');
            $table->bigInteger('amount');
            $table->string('payment_method', 24)->default('cash'); // cash|bank|ewallet
            $table->unsignedBigInteger('cash_account_id')->nullable(); // Phase 7.4 wire
            $table->json('applied_bills')->nullable();      // [{vendor_bill_id, applied_amount}]
            $table->string('memo', 500)->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->string('status', 16)->default('draft'); // draft|confirmed|cancelled
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'supplier_id', 'paid_at']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('vendor_bills');
    }
};
