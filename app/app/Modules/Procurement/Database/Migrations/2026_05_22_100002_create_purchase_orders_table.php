<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.1 — Đơn mua (Purchase Order). draft → confirmed → partially_received → received | cancelled.
 *
 * 1 PO **có thể** được nhận thành nhiều đợt (`goods_receipts` link bằng `po_id`); `qty_received` ở từng
 * dòng PO cộng dồn khi mỗi GoodsReceipt được confirm. Khi tất cả dòng `qty_received >= qty_ordered` ⇒ PO
 * tự chuyển `received`; còn thiếu ⇒ `partially_received`. Cancel chỉ được khi PO ở `draft`. SPEC 0014.
 *
 * `total_cost` = Σ(qty_ordered × unit_cost) chốt ở `confirmed_at`; không update sau (kế toán immutable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 32);                         // PO-YYYYMM-NNNN — unique trong tenant
            $table->foreignId('supplier_id');
            $table->foreignId('warehouse_id');                  // kho nhập đích mặc định
            $table->string('status', 24)->default('draft');     // draft|confirmed|partially_received|received|cancelled
            $table->date('expected_at')->nullable();            // ngày dự kiến giao
            $table->string('note', 500)->nullable();
            $table->integer('total_qty')->default(0);           // Σ qty_ordered (cập nhật khi add/remove items)
            $table->bigInteger('total_cost')->default(0);       // VND đồng — chốt ở confirm
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'supplier_id']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('purchase_order_id');
            $table->foreignId('sku_id');
            $table->integer('qty_ordered');
            $table->integer('qty_received')->default(0);        // cộng dồn từ các GoodsReceipt liên kết PO
            $table->bigInteger('unit_cost');                    // VND đồng — chốt ở confirm PO (lấy từ supplier_prices nếu không nhập)
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['purchase_order_id', 'sku_id']);
            $table->index(['tenant_id', 'sku_id']);
        });

        // Link GoodsReceipt → PO (nullable; 1 GoodsReceipt = 1 đợt nhận của 1 PO, hoặc nhập "đời tự do" như cũ).
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('warehouse_id')->index();
            $table->foreignId('supplier_id')->nullable()->after('purchase_order_id')->index();   // override `supplier` (free-text)
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['purchase_order_id', 'supplier_id']);
        });
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
