<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.4 — Bảng `payments` — BelongsToTenant.
 *
 * Idempotency: unique `(gateway, external_ref)` đảm bảo webhook chạy 2 lần
 * không tạo row trùng (SPEC 0018 §4.4).
 *
 * `raw_payload` chỉ lưu metadata không nhạy cảm (transaction_id, amount, bank_code, status,
 * time) — KHÔNG PAN/CVV (PCI scope minimization, SPEC 0018 §8 + docs/08-security-and-privacy.md §3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('invoice_id');
            $table->string('gateway', 16)->index();          // sepay|vnpay|momo|manual
            $table->string('external_ref', 128);             // mã giao dịch của cổng
            $table->bigInteger('amount');
            $table->string('status', 16)->index();           // pending|succeeded|failed|refunded
            $table->json('raw_payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'external_ref'], 'payments_gateway_ref_uniq');
            $table->index(['tenant_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
