<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.1 — SPEC 0019: bút toán nhật ký (Journal Entry — bất biến).
 *
 *  - Không `updated_at` (sửa = đảo + post mới).
 *  - `idempotency_key` unique per tenant — listener tự build key theo `"{module}.{type}.{id}.{kind}"`.
 *  - `total_debit` / `total_credit` denormalized — luôn bằng nhau (kiểm trong JournalService).
 *  - `source_*` cho phép truy ngược chứng từ gốc (đơn / phiếu nhập / settlement / ...).
 *  - `is_reversal_of_id` self-ref — entry đảo entry gốc (swap Dr/Cr).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            // 'JE-YYYYMM-NNNN' per tenant — sequence per (tenant, period).
            $table->string('code', 24);
            $table->timestamp('posted_at');
            $table->foreignId('period_id');
            $table->string('narration', 500)->nullable();

            // 'inventory' | 'orders' | 'finance' | 'procurement' | 'billing' | 'cash' | 'manual' | 'opening'
            $table->string('source_module', 24);
            // 'goods_receipt' | 'stock_transfer' | 'stocktake' | 'order' | 'settlement' | 'vendor_bill' | 'cash_movement' | 'manual'
            $table->string('source_type', 48);
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('idempotency_key', 191);
            $table->boolean('is_adjustment')->default(false);
            $table->foreignId('is_reversal_of_id')->nullable();
            $table->foreignId('adjusted_period_id')->nullable();

            $table->bigInteger('total_debit');
            $table->bigInteger('total_credit');
            $table->string('currency', 8)->default('VND');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['tenant_id', 'idempotency_key']);
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'period_id', 'posted_at']);
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'je_source_idx');
            $table->index(['tenant_id', 'is_reversal_of_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
