<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.1 — Giá vốn FIFO + sổ COGS của đơn (chuẩn kế toán).
 *
 * - `cost_layers`: mỗi lô nhập (GoodsReceipt confirm / stocktake_in / opening) tạo 1 layer. Khi `order_ship`
 *   thì rút FIFO (oldest first) trên `received_at`; `qty_remaining` giảm dần, hết ⇒ `exhausted_at`. Lưu lại
 *   `source_*` để truy nguồn gốc.
 * - `order_costs`: ghi nhận COGS THỰC của từng `order_item` khi đơn `shipped` (bất biến — không updated_at,
 *   không soft-delete). `layers_used` jsonb lưu phân rã layer × qty × unit_cost ⇒ audit ledger đầy đủ.
 *
 * SPEC 0014. docs/03-domain/inventory-and-sku-mapping.md §FIFO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('sku_id');
            $table->foreignId('warehouse_id')->nullable();           // null = không gắn kho (opening cũ)
            $table->string('source_type', 32);                       // goods_receipt | stocktake_in | opening | adjust_in
            $table->unsignedBigInteger('source_id')->nullable();     // FK theo source_type (không enforce — nhiều bảng)
            $table->timestamp('received_at');                        // FIFO key
            $table->bigInteger('unit_cost');                         // VND đồng
            $table->integer('qty_received');
            $table->integer('qty_remaining');
            $table->timestamp('exhausted_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'sku_id', 'received_at']);
            $table->index(['tenant_id', 'sku_id', 'qty_remaining']);
            $table->unique(['tenant_id', 'source_type', 'source_id', 'sku_id'], 'cost_layer_source_unique');
        });

        Schema::create('order_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('order_id');
            $table->foreignId('order_item_id');
            $table->foreignId('sku_id');
            $table->integer('qty');
            $table->bigInteger('cogs_unit_avg');                     // ≈ cogs_total / qty (làm tròn) — tham chiếu nhanh
            $table->bigInteger('cogs_total');                        // Σ (layer_qty × layer_unit_cost)
            $table->string('cost_method', 16);                       // fifo | average | latest (tại thời điểm ship)
            $table->json('layers_used')->nullable();                 // [{layer_id, qty, unit_cost}]
            $table->timestamp('shipped_at');
            $table->timestamp('created_at');
            $table->unique('order_item_id');
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'sku_id', 'shipped_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_costs');
        Schema::dropIfExists('cost_layers');
    }
};
