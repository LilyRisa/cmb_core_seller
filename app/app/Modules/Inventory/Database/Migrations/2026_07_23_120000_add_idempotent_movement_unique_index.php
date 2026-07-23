<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes a race in InventoryLedgerService::apply(): the app-level "does a movement for this
 * (sku, warehouse, ref_type, ref_id, type) already exist?" check ran before the row lock, so
 * two concurrent workers processing the same order event could both pass it and both write —
 * confirmed in prod (2 identical `order_release` rows, same ref_id, same timestamp, drove
 * `reserved` negative). This partial unique index makes the DB the real backstop; it only
 * covers the movement types InventoryLedgerService treats as idempotent (reserve/release/ship/
 * return_in) — goods_issue/transfer/stocktake_adjust are intentionally untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX inventory_movements_idempotent_unique
             ON inventory_movements (sku_id, warehouse_id, ref_type, ref_id, type)
             WHERE type IN (\'order_reserve\', \'order_release\', \'order_ship\', \'return_in\')
               AND ref_type IS NOT NULL AND ref_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_movements')) {
            DB::statement('DROP INDEX IF EXISTS inventory_movements_idempotent_unique');
        }
    }
};
