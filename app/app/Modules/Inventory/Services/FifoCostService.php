<?php

namespace CMBcoreSeller\Modules\Inventory\Services;

use CMBcoreSeller\Modules\Inventory\Models\CostLayer;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * FIFO cost layers — chuẩn kế toán. SPEC 0014.
 *
 *  - {@see recordReceiptLayer()}: idempotent qua unique `(tenant, source_type, source_id, sku)`.
 *    Gọi từ `WarehouseDocumentService::confirmGoodsReceipt` (mỗi dòng phiếu nhập = 1 layer mới).
 *  - {@see consumeForShip()}: rút FIFO layer cũ nhất khi `order_ship`; lock row `FOR UPDATE`; ghi
 *    {@see OrderCost} bất biến gắn `order_item`. Nếu tồn FIFO không đủ (chưa nhập đủ giá vốn) ⇒ tạo
 *    layer dự phòng với `unit_cost = Sku.effectiveCost()` (đảm bảo COGS không trống) và đánh dấu trong
 *    `layers_used` là `synthetic=true` — kế toán sau có thể đối chiếu.
 *  - {@see unconsume()}: hoàn layer khi hủy đơn / trả hàng (đảo `qty_remaining`).
 */
class FifoCostService
{
    /**
     * Tạo cost layer cho 1 lần nhập. Idempotent qua `(tenant, source_type, source_id, sku_id)`.
     * Nếu đã tồn tại (gọi lại lần 2 vì retry queue) ⇒ no-op.
     */
    public function recordReceiptLayer(
        int $tenantId,
        int $skuId,
        ?int $warehouseId,
        int $qty,
        int $unitCost,
        string $sourceType,
        ?int $sourceId,
        ?Carbon $receivedAt = null,
    ): ?CostLayer {
        if ($qty <= 0) {
            return null;
        }
        $existing = CostLayer::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('source_type', $sourceType)
            ->where('source_id', $sourceId)->where('sku_id', $skuId)->first();
        if ($existing) {
            return $existing;
        }

        return CostLayer::query()->create([
            'tenant_id' => $tenantId, 'sku_id' => $skuId, 'warehouse_id' => $warehouseId,
            'source_type' => $sourceType, 'source_id' => $sourceId,
            'received_at' => $receivedAt ?? now(),
            'unit_cost' => max(0, $unitCost), 'qty_received' => $qty, 'qty_remaining' => $qty,
        ]);
    }

    /**
     * Rút FIFO khi `order_ship`. Lock các layer còn `qty_remaining > 0` của SKU theo `received_at ASC`,
     * trừ dần đến hết `qty`. Tạo `OrderCost` (unique theo `order_item_id` — idempotent: ship lại cùng đơn
     * ⇒ no-op trả về row cũ). Trả về {@see OrderCost}.
     *
     * Nếu layer tồn không đủ → phát sinh "synthetic layer" ở `Sku.effective_cost` để COGS không null.
     */
    public function consumeForShip(
        int $tenantId,
        int $orderId,
        int $orderItemId,
        int $skuId,
        int $qty,
        ?int $warehouseId,
        ?Carbon $shippedAt = null,
        string $costMethod = 'fifo',
    ): ?OrderCost {
        if ($qty <= 0) {
            return null;
        }
        $existing = OrderCost::withoutGlobalScope(TenantScope::class)->where('order_item_id', $orderItemId)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($tenantId, $orderId, $orderItemId, $skuId, $qty, $warehouseId, $shippedAt, $costMethod) {
            $remaining = $qty;
            $layersUsed = [];
            $total = 0;
            $layersQuery = CostLayer::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('sku_id', $skuId)
                ->where('qty_remaining', '>', 0)
                ->orderBy('received_at')->orderBy('id');
            if ($warehouseId) {
                // Ưu tiên cùng kho (giảm cross-warehouse cost mixing), rồi đến các kho khác.
                $layersQuery->orderByRaw('warehouse_id = ? DESC', [$warehouseId]);
            }
            $layers = $layersQuery->lockForUpdate()->get();
            foreach ($layers as $layer) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($remaining, (int) $layer->qty_remaining);
                if ($take <= 0) {
                    continue;
                }
                $layer->forceFill([
                    'qty_remaining' => (int) $layer->qty_remaining - $take,
                    'exhausted_at' => ((int) $layer->qty_remaining - $take) === 0 ? now() : $layer->exhausted_at,
                ])->save();
                $layersUsed[] = ['layer_id' => (int) $layer->getKey(), 'qty' => $take, 'unit_cost' => (int) $layer->unit_cost];
                $total += $take * (int) $layer->unit_cost;
                $remaining -= $take;
            }
            // Tồn FIFO chưa đủ ⇒ tạo synthetic layer với giá vốn ước tính, đảm bảo có COGS để báo cáo.
            if ($remaining > 0) {
                $estUnit = $this->estimateUnitCost($tenantId, $skuId);
                $layersUsed[] = ['layer_id' => null, 'qty' => $remaining, 'unit_cost' => $estUnit, 'synthetic' => true];
                $total += $remaining * $estUnit;
                $remaining = 0;
            }

            return OrderCost::query()->create([
                'tenant_id' => $tenantId, 'order_id' => $orderId, 'order_item_id' => $orderItemId,
                'sku_id' => $skuId, 'qty' => $qty,
                'cogs_unit_avg' => $qty > 0 ? (int) round($total / $qty) : 0, 'cogs_total' => (int) $total,
                'cost_method' => $costMethod, 'layers_used' => $layersUsed,
                'shipped_at' => $shippedAt ?? now(), 'created_at' => now(),
            ]);
        });
    }

    /** Hoàn các layer khi đơn bị huỷ / trả hàng (đảo `qty_remaining` theo `layers_used`). Idempotent. */
    public function unconsume(int $tenantId, int $orderItemId): void
    {
        $oc = OrderCost::withoutGlobalScope(TenantScope::class)->where('order_item_id', $orderItemId)->first();
        if (! $oc) {
            return;
        }
        DB::transaction(function () use ($oc) {
            foreach ((array) $oc->layers_used as $u) {
                $layerId = $u['layer_id'] ?? null;
                $qty = (int) ($u['qty'] ?? 0);
                if (! $layerId || $qty <= 0) {
                    continue;
                }
                /** @var CostLayer|null $layer */
                $layer = CostLayer::withoutGlobalScope(TenantScope::class)->lockForUpdate()->find($layerId);
                if (! $layer) {
                    continue;
                }
                $layer->forceFill([
                    'qty_remaining' => min((int) $layer->qty_received, (int) $layer->qty_remaining + $qty),
                    'exhausted_at' => null,
                ])->save();
            }
            $oc->delete();
        });
    }

    private function estimateUnitCost(int $tenantId, int $skuId): int
    {
        /** @var Sku|null $sku */
        $sku = Sku::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->find($skuId);
        if (! $sku) {
            return 0;
        }
        $latest = (int) ($sku->last_receipt_cost ?? 0);
        $avg = (int) ($sku->cost_price ?? 0);

        return $sku->cost_method === 'latest' && $latest > 0 ? $latest : ($avg > 0 ? $avg : $latest);
    }
}
