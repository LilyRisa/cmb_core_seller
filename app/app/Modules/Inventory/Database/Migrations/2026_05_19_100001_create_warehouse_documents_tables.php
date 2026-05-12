<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 WMS — "phiếu" có header + duyệt: nhập kho (`goods_receipts`), chuyển kho
 * (`stock_transfers`), kiểm kê (`stocktakes`). Mỗi phiếu draft → confirmed → cancelled;
 * khi confirm thì áp vào sổ cái tồn (`InventoryLedgerService`) — nhập kho cũng cập nhật
 * giá vốn bình quân theo kho. See SPEC 0010, docs/02-data-model/overview.md, docs/03-domain/inventory-and-sku-mapping.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Phiếu nhập kho ---
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code');                              // PNK-...
            $table->foreignId('warehouse_id');
            $table->string('supplier')->nullable();              // free-text NCC (bảng suppliers ở Phase 6)
            $table->string('note')->nullable();
            $table->string('status')->default('draft');          // draft | confirmed | cancelled
            $table->bigInteger('total_cost')->default(0);        // VND đồng
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('goods_receipt_id');
            $table->foreignId('sku_id');
            $table->integer('qty');
            $table->bigInteger('unit_cost')->default(0);
            $table->index(['tenant_id', 'goods_receipt_id']);
        });

        // --- Phiếu chuyển kho ---
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code');                              // PCK-...
            $table->foreignId('from_warehouse_id');
            $table->foreignId('to_warehouse_id');
            $table->string('note')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stock_transfer_id');
            $table->foreignId('sku_id');
            $table->integer('qty');
            $table->index(['tenant_id', 'stock_transfer_id']);
        });

        // --- Phiếu kiểm kê ---
        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code');                              // PKK-...
            $table->foreignId('warehouse_id');
            $table->string('note')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stocktake_id');
            $table->foreignId('sku_id');
            $table->integer('system_qty')->default(0);           // snapshot lúc confirm (hoặc lúc thêm dòng)
            $table->integer('counted_qty');
            $table->integer('diff')->default(0);                 // counted - system
            $table->index(['tenant_id', 'stocktake_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocktake_items');
        Schema::dropIfExists('stocktakes');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
