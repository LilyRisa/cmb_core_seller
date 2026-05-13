<?php

namespace CMBcoreSeller\Modules\Procurement\Services;

use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceiptItem;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrder;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrderItem;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use CMBcoreSeller\Modules\Procurement\Models\SupplierPrice;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tạo + chốt + nhận PO. Mọi thao tác bất biến sau confirm (kế toán):
 *  - `create`/`update`: chỉ chạy được khi `draft`; cập nhật `total_qty`/`total_cost` tự động.
 *  - `confirm`: `draft → confirmed`, chốt `unit_cost` từng dòng (lấy `supplier_prices.is_default` nếu trống),
 *    `total_cost = Σ qty_ordered × unit_cost`. Idempotent (đã confirm ⇒ no-op).
 *  - `cancel`: `draft → cancelled` (mọi status khác ⇒ ném); idempotent.
 *  - `receive`: tạo {@see GoodsReceipt} `draft` mới link về PO; SỐ LƯỢNG `qty_received` cộng dồn ở
 *    `applyReceiptConfirmed()` (gọi từ `WarehouseDocumentService::confirmGoodsReceipt`); đủ tất cả dòng
 *    ⇒ `PO.status = received`, chưa đủ ⇒ `partially_received`. SPEC 0014.
 */
class PurchaseOrderService
{
    /** @param  array{supplier_id:int,warehouse_id:int,expected_at?:string|null,note?:string|null,items?:list<array{sku_id:int,qty_ordered:int,unit_cost?:int|null,note?:string|null}>}  $data */
    public function create(int $tenantId, array $data, ?int $userId = null): PurchaseOrder
    {
        $this->assertSupplier($tenantId, $data['supplier_id']);
        $this->assertWarehouse($tenantId, $data['warehouse_id']);

        return DB::transaction(function () use ($tenantId, $data, $userId) {
            $po = PurchaseOrder::query()->create([
                'tenant_id' => $tenantId, 'code' => PurchaseOrder::nextCode($tenantId),
                'supplier_id' => (int) $data['supplier_id'], 'warehouse_id' => (int) $data['warehouse_id'],
                'status' => PurchaseOrder::STATUS_DRAFT,
                'expected_at' => $data['expected_at'] ?? null,
                'note' => $data['note'] ?? null, 'created_by' => $userId,
            ]);
            if (! empty($data['items'])) {
                $this->setItems($po, $tenantId, $data['items']);
            }

            return $po->refresh()->load('items');
        });
    }

    /** Sửa header / thay items (chỉ `draft`). */
    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            throw new RuntimeException('Chỉ sửa được PO ở trạng thái nháp.');
        }

        return DB::transaction(function () use ($po, $data) {
            $patch = array_intersect_key($data, array_flip(['expected_at', 'note']));
            if (isset($data['supplier_id'])) {
                $this->assertSupplier((int) $po->tenant_id, (int) $data['supplier_id']);
                $patch['supplier_id'] = (int) $data['supplier_id'];
            }
            if (isset($data['warehouse_id'])) {
                $this->assertWarehouse((int) $po->tenant_id, (int) $data['warehouse_id']);
                $patch['warehouse_id'] = (int) $data['warehouse_id'];
            }
            if ($patch !== []) {
                $po->forceFill($patch)->save();
            }
            if (array_key_exists('items', $data)) {
                $this->setItems($po, (int) $po->tenant_id, (array) $data['items']);
            }

            return $po->refresh()->load('items');
        });
    }

    public function confirm(PurchaseOrder $po, ?int $userId = null): PurchaseOrder
    {
        if ($po->status === PurchaseOrder::STATUS_CONFIRMED || in_array($po->status, [PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED], true)) {
            return $po;   // idempotent
        }
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            throw new RuntimeException('PO không ở trạng thái nháp.');
        }
        $items = $po->items()->get();
        if ($items->isEmpty()) {
            throw new RuntimeException('PO chưa có dòng hàng nào.');
        }

        return DB::transaction(function () use ($po, $items, $userId) {
            // Chốt unit_cost: lấy giá NCC mặc định nếu dòng chưa có giá.
            $defaultPrices = SupplierPrice::query()->where('supplier_id', $po->supplier_id)
                ->whereIn('sku_id', $items->pluck('sku_id')->all())
                ->where('is_default', true)->get()->keyBy('sku_id');
            $totalQty = 0;
            $totalCost = 0;
            foreach ($items as $it) {
                $unit = (int) $it->unit_cost;
                if ($unit <= 0 && $defaultPrices->has($it->sku_id)) {
                    $unit = (int) $defaultPrices[$it->sku_id]->unit_cost;
                    $it->forceFill(['unit_cost' => $unit])->save();
                }
                $totalQty += (int) $it->qty_ordered;
                $totalCost += $unit * (int) $it->qty_ordered;
            }
            $po->forceFill([
                'status' => PurchaseOrder::STATUS_CONFIRMED,
                'total_qty' => $totalQty, 'total_cost' => $totalCost,
                'confirmed_at' => now(), 'confirmed_by' => $userId,
            ])->save();

            return $po->refresh()->load('items');
        });
    }

    public function cancel(PurchaseOrder $po, ?int $userId = null): PurchaseOrder
    {
        if ($po->status === PurchaseOrder::STATUS_CANCELLED) {
            return $po;   // idempotent
        }
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            throw new RuntimeException('Chỉ huỷ được PO ở trạng thái nháp. PO đã chốt phải tạo phiếu điều chỉnh kế toán riêng.');
        }
        $po->forceFill(['status' => PurchaseOrder::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $userId])->save();

        return $po->refresh();
    }

    /**
     * Tạo {@see GoodsReceipt} `draft` mới để nhận một đợt (lines: từng SKU + qty). Mỗi dòng phải còn tồn dư
     * `qty_ordered - qty_received` ở PO. Người dùng confirm GoodsReceipt sau (ở màn WMS) → áp tồn + cập nhật
     * giá vốn; listener `LinkGoodsReceiptToPO` (nối ở `WarehouseDocumentService`) sẽ cộng dồn `qty_received`.
     *
     * @param  list<array{sku_id:int,qty:int,unit_cost?:int|null}>  $lines
     */
    public function receive(PurchaseOrder $po, array $lines, ?int $userId = null): GoodsReceipt
    {
        if (! in_array($po->status, [PurchaseOrder::STATUS_CONFIRMED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
            throw new RuntimeException('PO chưa được chốt hoặc đã nhận đủ — không thể tạo đợt nhận.');
        }
        $itemsById = $po->items()->get()->keyBy('sku_id');
        foreach ($lines as $l) {
            $skuId = (int) ($l['sku_id'] ?? 0);
            $qty = (int) ($l['qty'] ?? 0);
            if (! $itemsById->has($skuId)) {
                throw new RuntimeException("SKU #{$skuId} không có trong PO này.");
            }
            if ($qty <= 0) {
                throw new RuntimeException('Số lượng nhận phải > 0.');
            }
            $remaining = (int) $itemsById[$skuId]->qty_ordered - (int) $itemsById[$skuId]->qty_received;
            if ($qty > $remaining) {
                throw new RuntimeException("SKU #{$skuId}: nhận {$qty} vượt số còn lại ({$remaining}).");
            }
        }

        return DB::transaction(function () use ($po, $lines, $itemsById, $userId) {
            $tenantId = (int) $po->tenant_id;
            $code = $this->nextGoodsReceiptCode($tenantId);
            $receipt = GoodsReceipt::query()->create([
                'tenant_id' => $tenantId, 'code' => $code, 'warehouse_id' => $po->warehouse_id,
                'purchase_order_id' => $po->getKey(), 'supplier_id' => $po->supplier_id,
                'supplier' => null, 'note' => 'Nhận từ PO '.$po->code,
                'status' => GoodsReceipt::STATUS_DRAFT, 'total_cost' => 0, 'created_by' => $userId,
            ]);
            foreach ($lines as $l) {
                $unit = isset($l['unit_cost']) && (int) $l['unit_cost'] > 0
                    ? (int) $l['unit_cost'] : (int) $itemsById[(int) $l['sku_id']]->unit_cost;
                GoodsReceiptItem::query()->create([
                    'tenant_id' => $tenantId, 'goods_receipt_id' => $receipt->getKey(),
                    'sku_id' => (int) $l['sku_id'], 'qty' => (int) $l['qty'], 'unit_cost' => $unit,
                ]);
            }

            return $receipt->refresh()->load('items');
        });
    }

    /**
     * Hook gọi từ `WarehouseDocumentService::confirmGoodsReceipt` SAU khi receipt đã `confirmed`. Cộng dồn
     * `qty_received` ở PO items + chuyển status PO. Idempotent: nếu `qty_received` đã được cộng (gọi lần 2
     * với cùng `goods_receipt_id`) thì no-op (kiểm bằng `meta.applied_to_po_at` không có trường hợp này
     * vì controller chỉ confirm 1 lần, nhưng vẫn an toàn hoá thông qua khoá row).
     */
    public function applyReceiptConfirmed(GoodsReceipt $receipt): void
    {
        if (! $receipt->purchase_order_id) {
            return;
        }
        DB::transaction(function () use ($receipt) {
            /** @var PurchaseOrder|null $po */
            $po = PurchaseOrder::withoutGlobalScope(TenantScope::class)->lockForUpdate()->find($receipt->purchase_order_id);
            if (! $po || $po->status === PurchaseOrder::STATUS_RECEIVED || $po->status === PurchaseOrder::STATUS_CANCELLED) {
                return;
            }
            $byId = $po->items()->lockForUpdate()->get()->keyBy('sku_id');
            $allReceived = true;
            foreach ($receipt->items as $rit) {
                if (! $byId->has($rit->sku_id)) {
                    continue;
                }
                /** @var PurchaseOrderItem $poItem */
                $poItem = $byId[$rit->sku_id];
                $newQty = min((int) $poItem->qty_ordered, (int) $poItem->qty_received + (int) $rit->qty);
                $poItem->forceFill(['qty_received' => $newQty])->save();
            }
            foreach ($po->items()->get() as $it) {
                if ((int) $it->qty_received < (int) $it->qty_ordered) {
                    $allReceived = false;
                    break;
                }
            }
            $po->forceFill(['status' => $allReceived ? PurchaseOrder::STATUS_RECEIVED : PurchaseOrder::STATUS_PARTIALLY_RECEIVED])->save();
        });
    }

    // ----- internals --------------------------------------------------------

    /** @param  list<array{sku_id:int,qty_ordered:int,unit_cost?:int|null,note?:string|null}>  $rows */
    private function setItems(PurchaseOrder $po, int $tenantId, array $rows): void
    {
        $skuIds = collect($rows)->pluck('sku_id')->filter()->map('intval')->unique();
        $validSkus = $skuIds->isEmpty() ? collect() : Sku::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereIn('id', $skuIds->all())->pluck('id');
        $missing = $skuIds->diff($validSkus);
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('SKU không hợp lệ: '.$missing->implode(', '));
        }

        // Replace toàn bộ items (PO ở draft)
        $po->items()->delete();
        $totalQty = 0;
        $totalCost = 0;
        foreach ($rows as $r) {
            $qty = max(1, (int) ($r['qty_ordered'] ?? 0));
            $unit = max(0, (int) ($r['unit_cost'] ?? 0));
            PurchaseOrderItem::query()->create([
                'tenant_id' => $tenantId, 'purchase_order_id' => $po->getKey(),
                'sku_id' => (int) $r['sku_id'], 'qty_ordered' => $qty, 'qty_received' => 0,
                'unit_cost' => $unit, 'note' => $r['note'] ?? null,
            ]);
            $totalQty += $qty;
            $totalCost += $qty * $unit;
        }
        $po->forceFill(['total_qty' => $totalQty, 'total_cost' => $totalCost])->save();
    }

    private function assertSupplier(int $tenantId, int $supplierId): void
    {
        $exists = Supplier::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->whereKey($supplierId)->whereNull('deleted_at')->exists();
        if (! $exists) {
            throw new RuntimeException('Nhà cung cấp không hợp lệ.');
        }
    }

    private function assertWarehouse(int $tenantId, int $warehouseId): void
    {
        $exists = Warehouse::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->whereKey($warehouseId)->exists();
        if (! $exists) {
            throw new RuntimeException('Kho không hợp lệ.');
        }
    }

    private function nextGoodsReceiptCode(int $tenantId): string
    {
        // Giống `WarehouseDocumentController::nextCode('goods-receipts')` (PNK-YYYYMMDD-NNNN) — đồng nhất
        // mã phiếu kho dù tạo từ flow WMS hay từ PO.
        $prefix = 'PNK-'.now()->format('Ymd').'-';
        $n = GoodsReceipt::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)
            ->where('code', 'like', $prefix.'%')->count() + 1;

        return sprintf('%s%04d', $prefix, $n);
    }
}
