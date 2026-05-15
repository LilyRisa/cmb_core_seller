<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.4 — SPEC 0019: Quỹ tiền mặt + tài khoản ngân hàng + sao kê.
 *
 *  - `cash_accounts`: gắn 1-1 với 1 TK GL (1111/1121/cod_intransit…). Tenant nhiều tài khoản ngân hàng
 *    đại diện ở đây — mỗi cash_account có code riêng.
 *  - `bank_statements` + `_lines`: import sao kê CSV/MT940/SePay webhook. Mỗi line match được sang
 *    `journal_lines` hoặc `customer_receipts/vendor_payments`.
 *
 * Mục tiêu: đối soát số dư ngân hàng. Sổ chi tiết quỹ đọc trực tiếp từ `journal_lines` của TK GL liên kết.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 32);
            $table->string('name', 255);
            $table->string('kind', 16); // cash | bank | ewallet | cod_intransit
            $table->string('bank_name', 100)->nullable();
            $table->string('account_no', 64)->nullable();
            $table->string('account_holder', 255)->nullable();
            $table->string('currency', 8)->default('VND');
            $table->foreignId('gl_account_id'); // FK chart_accounts (1111/1121/…)
            $table->boolean('is_active')->default(true);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'kind', 'is_active']);
        });

        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('cash_account_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('imported_from', 24); // csv|mt940|sepay_webhook|manual
            $table->integer('lines_count')->default(0);
            $table->bigInteger('total_in')->default(0);
            $table->bigInteger('total_out')->default(0);
            $table->string('status', 16)->default('imported'); // importing|imported|reconciled
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'cash_account_id', 'period_start']);
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('bank_statement_id');
            $table->timestamp('txn_date');
            $table->bigInteger('amount'); // signed: + = thu (in), - = chi (out)
            $table->string('counter_party', 255)->nullable();
            $table->string('memo', 500)->nullable();
            $table->string('external_ref', 191)->nullable(); // mã GD ngân hàng
            $table->string('status', 16)->default('unmatched'); // unmatched|matched|ignored
            $table->string('matched_ref_type', 32)->nullable(); // customer_receipt|vendor_payment|journal_entry
            $table->unsignedBigInteger('matched_ref_id')->nullable();
            $table->unsignedBigInteger('matched_journal_entry_id')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'bank_statement_id', 'txn_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'external_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statements');
        Schema::dropIfExists('cash_accounts');
    }
};
