<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.1 — SPEC 0019: dòng bút toán (Journal Line — bất biến).
 *
 *  - Mỗi line chính xác 1 trong dr_amount/cr_amount > 0 — kiểm trong JournalService + CHECK app-level.
 *  - `account_code` denormalized snapshot ⇒ truy vấn báo cáo không cần join.
 *  - `posted_at` denormalized từ entry (key cho partition theo tháng trên Postgres — Phase sau bật khi
 *    volume > 1M lines; v1 dùng bảng phẳng + index `(tenant, account, posted_at)`).
 *  - 5 chiều phân tích: `party_*` (KH/NCC/NV), `dim_warehouse_id`, `dim_shop_id` (channel_account),
 *    `dim_sku_id`, `dim_order_id`. Là soft reference (không FK cứng — module Accounting đứng độc lập,
 *    xoá khách/NCC không làm bể sổ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('entry_id');
            $table->timestamp('posted_at');

            $table->foreignId('account_id');
            $table->string('account_code', 16);

            $table->bigInteger('dr_amount')->default(0);
            $table->bigInteger('cr_amount')->default(0);

            // 'customer' | 'supplier' | 'staff' | 'channel'
            $table->string('party_type', 16)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();

            $table->unsignedBigInteger('dim_warehouse_id')->nullable();
            $table->unsignedBigInteger('dim_shop_id')->nullable();
            $table->unsignedBigInteger('dim_sku_id')->nullable();
            $table->unsignedBigInteger('dim_order_id')->nullable();
            $table->string('dim_tax_code', 16)->nullable();

            $table->string('memo', 500)->nullable();
            $table->integer('line_no')->default(0);

            $table->index(['tenant_id', 'account_id', 'posted_at']);
            $table->index(['tenant_id', 'party_type', 'party_id', 'posted_at'], 'jl_party_idx');
            $table->index('entry_id');
            $table->index(['tenant_id', 'dim_warehouse_id', 'posted_at']);
            $table->index(['tenant_id', 'dim_shop_id', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
