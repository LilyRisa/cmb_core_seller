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
 *
 * The race already happened many times before this fix (confirmed on prod: 164 duplicate
 * groups going back to 2026-05-30 — every group shares the same qty_change per ref_id, i.e. the
 * SAME event re-applied, never two genuinely different events), so creating the index directly
 * fails with a unique-violation on existing data. `dedupeAndCorrect()` runs first: keeps the
 * earliest row per (sku,warehouse,ref_type,ref_id,type) group, deletes the rest, and corrects
 * the levels those extra rows had already been double-applied to.
 */
return new class extends Migration
{
    private const IDEMPOTENT_TYPES = ['order_reserve', 'order_release', 'order_ship', 'return_in'];

    public function up(): void
    {
        $this->dedupeAndCorrect();

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

    /**
     * For every (sku_id, warehouse_id, ref_type, ref_id, type) group with >1 row among the
     * idempotent types: keep the earliest row, delete the rest. `order_ship`/`return_in` extras
     * over/under-applied `on_hand` by their `qty_change` each — reverse that. `order_reserve`/
     * `order_release` extras only affected `reserved`, which is recomputed from scratch per
     * affected (sku,warehouse) via `recomputeReserved()` — immune to duplicate row COUNTS since
     * it only checks type *existence* per order_item, so it's correct regardless of how many
     * times an event was erroneously re-applied.
     */
    private function dedupeAndCorrect(): void
    {
        $dupGroups = DB::table('inventory_movements')
            ->select('sku_id', 'warehouse_id', 'ref_type', 'ref_id', 'type')
            ->whereIn('type', self::IDEMPOTENT_TYPES)
            ->whereNotNull('ref_type')->whereNotNull('ref_id')
            ->groupBy('sku_id', 'warehouse_id', 'ref_type', 'ref_id', 'type')
            ->havingRaw('count(*) > 1')
            ->get();

        /** @var array<string,int> tenant_id:sku_id:warehouse_id => on_hand delta to add back */
        $onHandCorrections = [];
        /** @var array<string,true> sku_id:warehouse_id touched by any duplicate group */
        $affectedLevels = [];

        foreach ($dupGroups as $g) {
            $rows = DB::table('inventory_movements')
                ->where('sku_id', $g->sku_id)->where('warehouse_id', $g->warehouse_id)
                ->where('ref_type', $g->ref_type)->where('ref_id', $g->ref_id)->where('type', $g->type)
                ->orderBy('id')->get(['id', 'tenant_id', 'qty_change']);

            $extra = $rows->slice(1);
            $affectedLevels["{$g->sku_id}:{$g->warehouse_id}"] = true;

            if (in_array($g->type, ['order_ship', 'return_in'], true)) {
                $key = "{$rows->first()->tenant_id}:{$g->sku_id}:{$g->warehouse_id}";
                $onHandCorrections[$key] = ($onHandCorrections[$key] ?? 0) - (int) $extra->sum('qty_change');
            }

            DB::table('inventory_movements')->whereIn('id', $extra->pluck('id')->all())->delete();
        }

        foreach ($onHandCorrections as $key => $delta) {
            if ($delta === 0) {
                continue;
            }
            [$tenantId, $skuId, $warehouseId] = explode(':', $key);
            DB::table('inventory_levels')
                ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)
                ->increment('on_hand', $delta);
        }

        foreach (array_keys($affectedLevels) as $key) {
            [$skuId, $warehouseId] = explode(':', $key);
            $this->recomputeReserved((int) $skuId, (int) $warehouseId);
        }
    }

    /** Reserved = Σ qty of order_items that still have an order_reserve with no matching
     * order_release/order_ship — mirrors InventoryLedgerService::hasOpenReservation(). */
    private function recomputeReserved(int $skuId, int $warehouseId): void
    {
        $reserveRefs = DB::table('inventory_movements')
            ->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)
            ->where('type', 'order_reserve')->where('ref_type', 'order_item')->whereNotNull('ref_id')
            ->distinct()->pluck('ref_id');

        $openTotal = 0;
        foreach ($reserveRefs as $refId) {
            $base = fn () => DB::table('inventory_movements')
                ->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)
                ->where('ref_type', 'order_item')->where('ref_id', $refId);
            if ($base()->where('type', 'order_release')->exists() || $base()->where('type', 'order_ship')->exists()) {
                continue;
            }
            $openTotal += (int) $base()->where('type', 'order_reserve')->orderBy('id')->value('qty_change');
        }

        $level = DB::table('inventory_levels')->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)->first();
        if (! $level) {
            return;
        }
        DB::table('inventory_levels')->where('id', $level->id)->update([
            'reserved' => $openTotal,
            'available_cached' => max(0, $level->on_hand - max(0, $openTotal) - $level->safety_stock),
            'is_negative' => ($level->on_hand - max(0, $openTotal)) < 0,
        ]);
    }
};
