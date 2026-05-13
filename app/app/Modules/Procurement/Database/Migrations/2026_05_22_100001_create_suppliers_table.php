<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.1 — Quản lý Nhà cung cấp (NCC) + bảng giá nhập.
 *
 * - `suppliers`: NCC theo tenant (mã + tên + liên hệ + điều khoản thanh toán). Soft-delete.
 * - `supplier_prices`: bảng giá nhập theo (NCC × SKU) — `valid_from/valid_to` để có nhiều mức giá theo thời kỳ;
 *   `is_default` cờ "giá mặc định khi tạo PO". Không soft-delete (lịch sử giá là dữ liệu kế toán).
 *
 * See SPEC 0014 (Procurement & FIFO COGS), docs/02-data-model/overview.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 64);                        // mã NCC (NCC-…) — unique trong tenant
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('tax_code', 32)->nullable();        // MST
            $table->string('address')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);   // số ngày công nợ (NET-30, …)
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('supplier_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('supplier_id');
            $table->foreignId('sku_id');
            $table->bigInteger('unit_cost');                    // VND đồng
            $table->unsignedInteger('moq')->default(1);         // min order qty
            $table->string('currency', 8)->default('VND');
            $table->date('valid_from')->nullable();             // null = vô thời hạn
            $table->date('valid_to')->nullable();
            $table->boolean('is_default')->default(false);      // dùng làm giá mặc định khi tạo PO
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'supplier_id']);
            $table->index(['tenant_id', 'sku_id']);
            $table->unique(['supplier_id', 'sku_id', 'valid_from'], 'sup_price_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_prices');
        Schema::dropIfExists('suppliers');
    }
};
