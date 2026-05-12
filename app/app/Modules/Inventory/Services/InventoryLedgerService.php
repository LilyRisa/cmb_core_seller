<?php

namespace CMBcoreSeller\Modules\Inventory\Services;

use CMBcoreSeller\Modules\Inventory\Events\InventoryChanged;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

/**
 * The only writer of stock. Every change: lock the level row, mutate
 * (on_hand/reserved), refresh `available_cached`, append an immutable
 * `inventory_movements` row with `balance_after`, fire {@see InventoryChanged}.
 * Order-linked ops are idempotent on `(ref_type, ref_id, sku_id, type)` so a
 * replayed OrderUpserted never double-counts. Runs with the tenant scope off and
 * an explicit tenant_id (called from a queued listener). See SPEC 0003 §4.
 */
class InventoryLedgerService
{
    /** Manual stock correction: on_hand += qtyChange. */
    public function adjust(int $tenantId, int $skuId, ?int $warehouseId, int $qtyChange, ?string $note = null, ?int $userId = null, ?string $refType = null, ?int $refId = null): InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: $qtyChange, reservedDelta: 0,
            type: InventoryMovement::MANUAL_ADJUST, qtyChange: $qtyChange, refType: $refType, refId: $refId, note: $note, userId: $userId, reason: 'manual_adjust');
    }

    /** Goods receipt (PO / initial stock): on_hand += qty. */
    public function receipt(int $tenantId, int $skuId, ?int $warehouseId, int $qty, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $userId = null): InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: $qty, reservedDelta: 0,
            type: InventoryMovement::GOODS_RECEIPT, qtyChange: $qty, refType: $refType, refId: $refId, note: $note, userId: $userId, reason: 'goods_receipt');
    }

    /** Hold stock for an order line: reserved += qty. Idempotent per (order_item, sku). */
    public function reserve(int $tenantId, int $skuId, int $qty, string $refType, int $refId, ?int $warehouseId = null, ?int $userId = null): ?InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: 0, reservedDelta: $qty,
            type: InventoryMovement::ORDER_RESERVE, qtyChange: $qty, refType: $refType, refId: $refId, note: null, userId: $userId, reason: 'order_reserve', idempotent: true);
    }

    /** Release a hold (cancel/return before ship): reserved -= qty. Idempotent. */
    public function release(int $tenantId, int $skuId, int $qty, string $refType, int $refId, ?int $warehouseId = null, ?int $userId = null): ?InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: 0, reservedDelta: -$qty,
            type: InventoryMovement::ORDER_RELEASE, qtyChange: -$qty, refType: $refType, refId: $refId, note: null, userId: $userId, reason: 'order_release', idempotent: true);
    }

    /**
     * Ship an order line: on_hand -= qty, and consume the reservation if one is
     * still open (reserved -= qty). Idempotent.
     */
    public function ship(int $tenantId, int $skuId, int $qty, string $refType, int $refId, bool $hadOpenReservation, ?int $warehouseId = null, ?int $userId = null): ?InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: -$qty, reservedDelta: $hadOpenReservation ? -$qty : 0,
            type: InventoryMovement::ORDER_SHIP, qtyChange: -$qty, refType: $refType, refId: $refId, note: null, userId: $userId, reason: 'order_ship', idempotent: true);
    }

    /** Returned goods come back to stock: on_hand += qty. Idempotent. */
    public function returnIn(int $tenantId, int $skuId, int $qty, string $refType, int $refId, ?int $warehouseId = null, ?int $userId = null): ?InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: $qty, reservedDelta: 0,
            type: InventoryMovement::RETURN_IN, qtyChange: $qty, refType: $refType, refId: $refId, note: null, userId: $userId, reason: 'return_in', idempotent: true);
    }

    /** Stock transfer leg out of a warehouse: on_hand -= qty (type=transfer_out). Phase 5 WMS. */
    public function transferOut(int $tenantId, int $skuId, int $fromWarehouseId, int $qty, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $userId = null): InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $fromWarehouseId, onHandDelta: -$qty, reservedDelta: 0,
            type: InventoryMovement::TRANSFER_OUT, qtyChange: -$qty, refType: $refType, refId: $refId, note: $note, userId: $userId, reason: 'transfer_out');
    }

    /** Stock transfer leg into a warehouse: on_hand += qty (type=transfer_in). Phase 5 WMS. */
    public function transferIn(int $tenantId, int $skuId, int $toWarehouseId, int $qty, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $userId = null): InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $toWarehouseId, onHandDelta: $qty, reservedDelta: 0,
            type: InventoryMovement::TRANSFER_IN, qtyChange: $qty, refType: $refType, refId: $refId, note: $note, userId: $userId, reason: 'transfer_in');
    }

    /** Stocktake correction: on_hand += diff (type=stocktake_adjust). Phase 5 WMS. */
    public function stocktakeAdjust(int $tenantId, int $skuId, ?int $warehouseId, int $diff, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $userId = null): InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: $diff, reservedDelta: 0,
            type: InventoryMovement::STOCKTAKE_ADJUST, qtyChange: $diff, refType: $refType, refId: $refId, note: $note, userId: $userId, reason: 'stocktake_adjust');
    }

    /** Read the current on_hand of (sku, warehouse). Used by stocktake to snapshot system_qty. */
    public function onHand(int $tenantId, int $skuId, int $warehouseId): int
    {
        return (int) (InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)->value('on_hand') ?? 0);
    }

    /**
     * Update the per-warehouse weighted-average cost after a goods receipt of `recvQty` units
     * at `recvUnitCost`. Called by GoodsReceipt confirm. (Phase 5 — "giá vốn bình quân"; FIFO `cost_layers` later.)
     */
    public function updateAverageCost(int $tenantId, int $skuId, int $warehouseId, int $recvQty, int $recvUnitCost): void
    {
        if ($recvQty <= 0) {
            return;
        }
        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)->first();
        if (! $level) {
            return;
        }
        $prevQty = max(0, (int) $level->on_hand - $recvQty);   // on_hand already includes the receipt
        $prevCost = (int) $level->cost_price;
        $newCost = (int) round(($prevQty * $prevCost + $recvQty * $recvUnitCost) / ($prevQty + $recvQty));
        $level->forceFill(['cost_price' => $newCost])->save();
    }

    /**
     * After a goods receipt of `recvQty` @ `recvUnitCost`: update the per-warehouse weighted-average
     * cost, remember it as the SKU's `last_receipt_cost`, and refresh the SKU's company-wide
     * weighted-average `cost_price`. See SPEC 0012 (giá vốn cho lợi nhuận ước tính).
     */
    public function recordReceiptCost(int $tenantId, int $skuId, int $warehouseId, int $recvQty, int $recvUnitCost): void
    {
        $this->updateAverageCost($tenantId, $skuId, $warehouseId, $recvQty, $recvUnitCost);

        // company-wide weighted avg = Σ(level.cost_price × on_hand) / Σ(on_hand) over all warehouses
        $levels = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->get(['cost_price', 'on_hand']);
        $totalQty = (int) $levels->sum('on_hand');
        $avg = $totalQty > 0
            ? (int) round($levels->sum(fn ($l) => (int) $l->cost_price * max(0, (int) $l->on_hand)) / $totalQty)
            : $recvUnitCost;

        Sku::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->where('id', $skuId)
            ->update(['last_receipt_cost' => $recvUnitCost, 'cost_price' => $avg]);
    }

    public function availableTotalForSku(int $tenantId, int $skuId): int
    {
        return (int) InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->sum('available_cached');
    }

    /**
     * Tồn vật lý còn lại sau khi trừ phần đã giữ chỗ trên mọi kho (∑ on_hand − ∑ reserved).
     * < 0 ⇒ SKU đã đặt vượt tồn ("âm tồn / hết hàng") — chặn "chuẩn bị hàng / in phiếu giao hàng". SPEC 0013.
     */
    public function netStockForSku(int $tenantId, int $skuId): int
    {
        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('sku_id', $skuId)
            ->selectRaw('COALESCE(SUM(on_hand), 0) - COALESCE(SUM(reserved), 0) AS net')->first();

        return (int) ($level->net ?? 0);
    }

    /** True if there's a still-open reservation for this (order_item, sku) — used by ship(). */
    public function hasOpenReservation(int $tenantId, int $skuId, string $refType, int $refId): bool
    {
        $q = fn (string $type) => InventoryMovement::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->where('ref_type', $refType)->where('ref_id', $refId)->where('type', $type)->exists();

        return $q(InventoryMovement::ORDER_RESERVE) && ! $q(InventoryMovement::ORDER_RELEASE) && ! $q(InventoryMovement::ORDER_SHIP);
    }

    public function movementExists(int $tenantId, int $skuId, string $refType, int $refId, string $type): bool
    {
        return InventoryMovement::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->where('ref_type', $refType)->where('ref_id', $refId)->where('type', $type)->exists();
    }

    // --- core -----------------------------------------------------------------

    private function apply(
        int $tenantId, int $skuId, ?int $warehouseId, int $onHandDelta, int $reservedDelta,
        string $type, int $qtyChange, ?string $refType, ?int $refId, ?string $note, ?int $userId, string $reason, bool $idempotent = false
    ): ?InventoryMovement {
        $warehouseId ??= Warehouse::defaultFor($tenantId)->getKey();

        $movement = DB::transaction(function () use ($tenantId, $skuId, $warehouseId, $onHandDelta, $reservedDelta, $type, $qtyChange, $refType, $refId, $note, $userId, $idempotent) {
            if ($idempotent && $refType !== null && $refId !== null
                && InventoryMovement::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)
                    ->where('ref_type', $refType)->where('ref_id', $refId)->where('type', $type)->exists()) {
                return null; // already applied — replay-safe
            }

            /** @var InventoryLevel $level */
            $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
                ->where('sku_id', $skuId)->where('warehouse_id', $warehouseId)->lockForUpdate()->first()
                ?? InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => $tenantId, 'sku_id' => $skuId, 'warehouse_id' => $warehouseId,
                    'on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => 0,
                ]);

            $level->on_hand += $onHandDelta;
            $level->reserved += $reservedDelta;
            $available = max(0, $level->on_hand - $level->reserved - $level->safety_stock);
            $level->available_cached = $available;
            $level->is_negative = ($level->on_hand - $level->reserved) < 0;
            $level->save();

            return InventoryMovement::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenantId, 'sku_id' => $skuId, 'warehouse_id' => $warehouseId,
                'qty_change' => $qtyChange, 'type' => $type, 'ref_type' => $refType, 'ref_id' => $refId,
                'balance_after' => $level->on_hand, 'note' => $note, 'created_by' => $userId, 'created_at' => now(),
            ]);
        });

        if ($movement !== null) {
            InventoryChanged::dispatch($tenantId, [$skuId], $reason);
        }

        return $movement;
    }
}
