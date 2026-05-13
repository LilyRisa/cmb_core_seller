<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.2 — Đối soát/Settlement của sàn. SPEC 0016.
 *
 *  - `settlements`: 1 kỳ đối soát/statement (per shop). `total_*` được aggregate khi upsert; `status`
 *    `pending` (mới kéo về, chưa map đơn) | `reconciled` (đã match line ↔ order) | `error`.
 *  - `settlement_lines`: từng dòng phí (commission/payment_fee/shipping/voucher/adjustment/refund/other).
 *    Bất biến (no updated_at). Dedupe theo `(settlement_id, fee_type, external_order_id, external_line_id)`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id');
            $table->string('external_id', 191)->nullable();           // statement id của sàn (nếu có)
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('currency', 8)->default('VND');
            $table->bigInteger('total_payout')->default(0);            // net seller nhận về (Σ amount)
            $table->bigInteger('total_revenue')->default(0);
            $table->bigInteger('total_fee')->default(0);                // tổng phí (âm — hoa hồng + payment + voucher seller)
            $table->bigInteger('total_shipping_fee')->default(0);
            $table->string('status', 24)->default('pending');           // pending | reconciled | error
            $table->json('raw')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'channel_account_id', 'period_start']);
            $table->unique(['tenant_id', 'channel_account_id', 'external_id'], 'settlement_external_unique');
        });

        Schema::create('settlement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('settlement_id');
            $table->foreignId('order_id')->nullable();                  // null đến khi reconcile
            $table->string('external_order_id', 191)->nullable();       // mã đơn của sàn — match → order_id
            $table->string('external_line_id', 191)->nullable();        // line/transaction id (nếu có)
            $table->string('fee_type', 24);                             // SettlementLineDTO::TYPES
            $table->bigInteger('amount');                               // VND đồng (dương: thu, âm: chi)
            $table->timestamp('occurred_at')->nullable();
            $table->string('description', 500)->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('created_at');
            $table->index(['tenant_id', 'settlement_id']);
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'external_order_id']);
            $table->index(['tenant_id', 'fee_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_lines');
        Schema::dropIfExists('settlements');
    }
};
