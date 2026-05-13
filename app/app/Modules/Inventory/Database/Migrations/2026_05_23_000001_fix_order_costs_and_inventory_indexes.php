<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit fixes:
 *  - order_costs: thay unique(order_item_id) bằng unique(tenant_id, order_item_id) để tenant isolation đúng.
 *  - inventory_movements: thêm index (tenant_id, sku_id, type) cho hasOpenReservation()
 *    và index (tenant_id, ref_type, ref_id) thay cho index (ref_type, ref_id) thiếu tenant_id prefix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_costs', function (Blueprint $table) {
            $table->dropUnique('order_costs_order_item_id_unique');
            $table->unique(['tenant_id', 'order_item_id'], 'order_costs_tenant_order_item_unique');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['ref_type', 'ref_id']);
            $table->index(['tenant_id', 'sku_id', 'type'], 'inv_movements_tenant_sku_type_idx');
            $table->index(['tenant_id', 'ref_type', 'ref_id'], 'inv_movements_tenant_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_costs', function (Blueprint $table) {
            $table->dropUnique('order_costs_tenant_order_item_unique');
            $table->unique('order_item_id');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inv_movements_tenant_sku_type_idx');
            $table->dropIndex('inv_movements_tenant_ref_idx');
            $table->index(['ref_type', 'ref_id']);
        });
    }
};
