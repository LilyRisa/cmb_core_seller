<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.4 — Bảng `invoices` (header) + `invoice_lines` (chi tiết).
 *
 * - `invoices`: BelongsToTenant; code `INV-YYYYMM-NNNN` unique theo tenant.
 * - `invoice_lines`: không có tenant_id (đi qua invoice).
 * - `customer_snapshot`: snapshot billing_profile lúc tạo invoice (immutable kế toán).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('subscription_id');
            $table->string('code', 32);                      // INV-YYYYMM-NNNN, unique per tenant
            $table->string('status', 16)->index();           // draft|pending|paid|void|refunded
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('subtotal');
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('total');
            $table->string('currency', 3)->default('VND');
            $table->timestamp('due_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('kind', 16);                      // plan|addon|discount
            $table->string('description', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_price');
            $table->bigInteger('amount');
            $table->timestamps();
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
