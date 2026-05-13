<?php

namespace CMBcoreSeller\Modules\Procurement\Services;

use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrder;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrderItem;
use CMBcoreSeller\Modules\Procurement\Models\SupplierPrice;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 6.3 — Đề xuất nhập hàng (Demand Planning).
 *
 * Công thức (chuẩn supply-chain căn bản, dễ hiểu cho chủ shop):
 *  1. **Tốc độ bán** `avg_daily_sold` = Σ `order_costs.qty` trong `window_days` qua / `window_days`.
 *     `order_costs` chỉ ghi khi đơn `shipped` (bất biến) ⇒ KHÔNG đếm đơn huỷ/trả; reliable.
 *  2. **Tồn khả dụng** `available` = `on_hand_total - reserved_total` (`InventoryLevel.available_cached` đã tính sẵn).
 *  3. **Đang về** `on_order` = Σ `(qty_ordered - qty_received)` trên PO đã `confirmed`/`partially_received`.
 *  4. **Số ngày còn hàng** = `(available + on_order) / max(1, avg_daily_sold)` (∞ nếu không bán gì).
 *  5. **Đề xuất nhập** `suggested_qty` = max(0, `avg_daily_sold × (lead_time + cover_days) − available − on_order`).
 *     Tròn lên đến `MOQ` của NCC mặc định nếu có. Nếu `avg_daily_sold == 0` nhưng `available < safety_stock` ⇒
 *     đề xuất nhập đủ tới `safety_stock`.
 *  6. **Mức độ** `urgency`:
 *      - `urgent`  : days_left ≤ lead_time (sắp hết hàng trước khi nhập kịp).
 *      - `soon`    : lead_time < days_left ≤ lead_time + cover_days.
 *      - `ok`      : còn hàng dư cover_days (không cần đặt).
 *
 * Lưu ý: bài toán cross-warehouse FIFO/đa kho nâng cao = follow-up; v1 gộp tồn toàn tenant.
 */
class DemandPlanningService
{
    /**
     * @param  array{q?:string,supplier_id?:int,urgency?:string,page?:int,per_page?:int}  $filters
     * @return array{items:list<array<string,mixed>>, total:int, page:int, per_page:int, params:array<string,mixed>}
     */
    public function compute(int $tenantId, int $windowDays, int $leadTimeDays, int $coverDays, array $filters = []): array
    {
        $windowDays = max(7, min(365, $windowDays));
        $leadTimeDays = max(0, min(120, $leadTimeDays));
        $coverDays = max(0, min(120, $coverDays));
        $since = Carbon::now()->subDays($windowDays);

        // 1) Tốc độ bán per SKU
        $velocity = OrderCost::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('shipped_at', '>=', $since)
            ->selectRaw('sku_id, SUM(qty) AS sold')->groupBy('sku_id')
            ->pluck('sold', 'sku_id')->map(fn ($v) => (int) $v);

        // 2) Tồn theo SKU (gộp các kho)
        $stocks = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->selectRaw('sku_id, SUM(on_hand) AS on_hand, SUM(reserved) AS reserved, SUM(safety_stock) AS safety_stock')
            ->groupBy('sku_id')
            ->get()->keyBy('sku_id');

        // 3) Đang về từ PO mở (confirmed / partially_received)
        $onOrder = PurchaseOrderItem::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('purchase_order_id', PurchaseOrder::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->whereIn('status', [PurchaseOrder::STATUS_CONFIRMED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
                ->select('id'))
            ->selectRaw('sku_id, SUM(qty_ordered - qty_received) AS remaining')->groupBy('sku_id')
            ->pluck('remaining', 'sku_id')->map(fn ($v) => max(0, (int) $v));

        // 4) Quét toàn bộ SKU active (filter q nếu có)
        $skuQuery = Sku::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('is_active', true);
        if ($q = trim((string) ($filters['q'] ?? ''))) {
            $skuQuery->where(fn ($w) => $w->where('sku_code', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"));
        }
        $skus = $skuQuery->get(['id', 'sku_code', 'name', 'image_url', 'cost_price', 'last_receipt_cost', 'category']);

        // 5) NCC gợi ý: lấy `is_default` cho SKU; nếu filter by `supplier_id` thì chỉ giữ SKU của NCC đó.
        $defaultPrices = SupplierPrice::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereIn('sku_id', $skus->pluck('id')->all())
            ->where('is_default', true)->with('supplier:id,name,code,payment_terms_days')->get()
            ->keyBy('sku_id');

        if (! empty($filters['supplier_id'])) {
            $supplierId = (int) $filters['supplier_id'];
            $skus = $skus->filter(fn ($s) => ($defaultPrices[$s->id] ?? null) && (int) $defaultPrices[$s->id]->supplier_id === $supplierId)->values();
        }

        // 6) Build từng dòng đề xuất
        $items = [];
        foreach ($skus as $sku) {
            $sold = (int) ($velocity[$sku->id] ?? 0);
            $stock = $stocks[$sku->id] ?? null;
            $onHand = (int) ($stock->on_hand ?? 0);
            $reserved = (int) ($stock->reserved ?? 0);
            $safety = (int) ($stock->safety_stock ?? 0);
            $available = max(0, $onHand - $reserved);
            $incoming = (int) ($onOrder[$sku->id] ?? 0);
            $avg = $sold > 0 ? round($sold / $windowDays, 3) : 0.0;

            $daysLeft = $avg > 0 ? (int) floor(($available + $incoming) / $avg) : ($available + $incoming > 0 ? 9999 : 0);
            $urgency = match (true) {
                $daysLeft <= $leadTimeDays => 'urgent',
                $daysLeft <= ($leadTimeDays + $coverDays) => 'soon',
                default => 'ok',
            };

            // suggested_qty
            $target = (int) ceil($avg * ($leadTimeDays + $coverDays));
            $needed = max(0, $target - $available - $incoming);
            if ($needed === 0 && $avg === 0.0 && $available < $safety) {
                $needed = $safety - $available;   // không bán gì nhưng dưới mức an toàn ⇒ vẫn nhập đủ
            }
            // tròn lên MOQ của NCC mặc định
            $priceRow = $defaultPrices[$sku->id] ?? null;
            if ($needed > 0 && $priceRow && (int) $priceRow->moq > 1) {
                $moq = (int) $priceRow->moq;
                $needed = (int) (ceil($needed / $moq) * $moq);
            }

            // filter theo urgency
            if (! empty($filters['urgency']) && $filters['urgency'] !== 'all' && $filters['urgency'] !== $urgency) {
                continue;
            }

            // bỏ qua dòng "ok" + needed=0 (không actionable)
            if ($urgency === 'ok' && $needed === 0) {
                continue;
            }

            $items[] = [
                'sku' => ['id' => $sku->id, 'sku_code' => $sku->sku_code, 'name' => $sku->name, 'image_url' => $sku->image_url, 'category' => $sku->category],
                'avg_daily_sold' => $avg,
                'sold_in_window' => $sold,
                'window_days' => $windowDays,
                'on_hand' => $onHand,
                'reserved' => $reserved,
                'available' => $available,
                'safety_stock' => $safety,
                'on_order' => $incoming,
                'days_left' => $daysLeft,
                'urgency' => $urgency,
                'suggested_qty' => $needed,
                'suggested_supplier' => $priceRow && $priceRow->supplier ? [
                    'id' => $priceRow->supplier->id, 'code' => $priceRow->supplier->code, 'name' => $priceRow->supplier->name,
                ] : null,
                'suggested_unit_cost' => $priceRow ? (int) $priceRow->unit_cost : (int) ($sku->last_receipt_cost ?? $sku->cost_price ?? 0),
                'suggested_cost_total' => $needed * ($priceRow ? (int) $priceRow->unit_cost : (int) ($sku->last_receipt_cost ?? $sku->cost_price ?? 0)),
            ];
        }

        // sort: urgent first, rồi suggested_qty desc
        usort($items, function ($a, $b) {
            $order = ['urgent' => 0, 'soon' => 1, 'ok' => 2];

            return ($order[$a['urgency']] <=> $order[$b['urgency']]) ?: ($b['suggested_qty'] <=> $a['suggested_qty']);
        });

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(200, (int) ($filters['per_page'] ?? 50)));
        $total = count($items);
        $items = array_slice($items, ($page - 1) * $perPage, $perPage);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'params' => [
            'window_days' => $windowDays, 'lead_time' => $leadTimeDays, 'cover_days' => $coverDays,
        ]];
    }

    /**
     * Tạo PO nháp từ các dòng đề xuất. Chia theo NCC: mỗi NCC = 1 PO mới (draft). Trả về list `purchase_order_id`.
     *
     * @param  list<array{sku_id:int, qty:int, supplier_id:int, unit_cost?:int|null}>  $rows
     * @return list<int> POs created
     */
    public function createPoFromSuggestions(int $tenantId, int $warehouseId, array $rows, ?int $userId = null): array
    {
        $bySupplier = [];
        foreach ($rows as $r) {
            $sid = (int) ($r['supplier_id'] ?? 0);
            $sku = (int) ($r['sku_id'] ?? 0);
            $qty = max(1, (int) ($r['qty'] ?? 0));
            if ($sid <= 0 || $sku <= 0) {
                continue;
            }
            $bySupplier[$sid][] = ['sku_id' => $sku, 'qty_ordered' => $qty, 'unit_cost' => (int) ($r['unit_cost'] ?? 0)];
        }
        if ($bySupplier === []) {
            return [];
        }
        $service = app(PurchaseOrderService::class);
        $createdIds = [];
        DB::transaction(function () use ($bySupplier, $tenantId, $warehouseId, $userId, $service, &$createdIds) {
            foreach ($bySupplier as $supplierId => $items) {
                $po = $service->create($tenantId, [
                    'supplier_id' => (int) $supplierId, 'warehouse_id' => $warehouseId,
                    'note' => 'Tự tạo từ đề xuất nhập hàng', 'items' => $items,
                ], $userId);
                $createdIds[] = (int) $po->getKey();
            }
        });

        return $createdIds;
    }
}
