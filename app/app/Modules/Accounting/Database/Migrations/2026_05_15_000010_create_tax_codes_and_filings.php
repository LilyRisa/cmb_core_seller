<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.5 — SPEC 0019: bảng mã VAT + tờ khai 01/GTGT (preview).
 *
 *  - `tax_codes`: bảng mã VAT (0/5/8/10/exempt) gắn với TK GL (1331 đầu vào / 33311 đầu ra).
 *  - `tax_filings`: tờ khai 01/GTGT theo kỳ — v1 chỉ aggregate + preview PDF + xuất XML mẫu;
 *    nộp tự động qua API CQT là follow-up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 16);              // VAT0|VAT5|VAT8|VAT10|VAT_EXEMPT
            $table->string('name', 100);
            $table->integer('rate_bps');             // basis points (0..10000)
            $table->string('kind', 8);               // output | input | both
            $table->string('gl_account_code', 16);   // 1331 hoặc 33311
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('tax_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 32);              // 01/GTGT-2026-05
            $table->foreignId('period_id');          // FK fiscal_periods (month)
            $table->string('tax_kind', 16)->default('vat'); // vat
            $table->string('status', 16)->default('draft'); // draft|submitted|paid
            $table->json('lines')->nullable();       // tổng hợp 01/GTGT
            $table->bigInteger('total_output_vat')->default(0);
            $table->bigInteger('total_input_vat')->default(0);
            $table->bigInteger('net_payable')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_filings');
        Schema::dropIfExists('tax_codes');
    }
};
