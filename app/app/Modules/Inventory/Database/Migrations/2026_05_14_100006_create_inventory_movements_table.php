<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * inventory_movements — the immutable stock ledger. Every stock change is one row
 * with `balance_after` (= on_hand of that warehouse after the change). No soft
 * delete. Monthly-partition target (kept as a plain table in Phase 2). See SPEC 0003 §5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('sku_id');
            $table->foreignId('warehouse_id');
            $table->integer('qty_change');
            $table->string('type', 32);     // manual_adjust|goods_receipt|order_reserve|order_release|order_ship|return_in|transfer_out|transfer_in|stocktake_adjust
            $table->string('ref_type')->nullable();   // e.g. 'order_item'
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->integer('balance_after');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'sku_id', 'id']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
