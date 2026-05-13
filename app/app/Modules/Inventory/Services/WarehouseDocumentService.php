<?php

namespace CMBcoreSeller\Modules\Inventory\Services;

use CMBcoreSeller\Modules\Inventory\Events\GoodsReceiptConfirmed;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use CMBcoreSeller\Modules\Inventory\Models\Stocktake;
use CMBcoreSeller\Modules\Inventory\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Confirms / cancels WMS "phiếu" (Phase 5 — SPEC 0010). On confirm, applies the document to
 * the stock ledger via {@see InventoryLedgerService}:
 *  - goods receipt → `receipt()` per line (+ weighted-average cost on the warehouse level)
 *  - transfer      → `transferOut()` from the source + `transferIn()` to the destination
 *  - stocktake     → `stocktakeAdjust()` for each line whose `diff != 0`
 * Confirm/cancel are one-way (draft → confirmed; draft → cancelled); confirmed phiếu are
 * immutable (a correction = a new phiếu) — keeps the ledger an honest audit trail.
 */
class WarehouseDocumentService
{
    public function __construct(private readonly InventoryLedgerService $ledger, private readonly FifoCostService $fifo) {}

    public function confirmGoodsReceipt(GoodsReceipt $doc, int $userId): GoodsReceipt
    {
        $this->assertDraft($doc->status);
        $tenantId = (int) $doc->tenant_id;
        $whId = (int) $doc->warehouse_id;
        $items = $doc->items;
        if ($items->isEmpty()) {
            throw new RuntimeException('Phiếu chưa có dòng hàng nào.');
        }
        DB::transaction(function () use ($doc, $items, $tenantId, $whId, $userId) {
            foreach ($items as $it) {
                if ((int) $it->qty <= 0) {
                    continue;
                }
                $this->ledger->receipt($tenantId, (int) $it->sku_id, $whId, (int) $it->qty, 'Nhập kho '.$doc->code, 'goods_receipt', (int) $doc->getKey(), $userId);
                if ((int) $it->unit_cost > 0) {
                    // per-warehouse weighted-avg cost + SKU.last_receipt_cost + SKU.cost_price (company-wide avg)
                    $this->ledger->recordReceiptCost($tenantId, (int) $it->sku_id, $whId, (int) $it->qty, (int) $it->unit_cost);
                    // FIFO layer (chuẩn kế toán) — idempotent qua (goods_receipt, sku). SPEC 0014.
                    $this->fifo->recordReceiptLayer($tenantId, (int) $it->sku_id, $whId, (int) $it->qty, (int) $it->unit_cost,
                        \CMBcoreSeller\Modules\Inventory\Models\CostLayer::SOURCE_GOODS_RECEIPT, (int) $doc->getKey());
                }
            }
            $doc->forceFill(['status' => GoodsReceipt::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => $userId,
                'total_cost' => (int) $items->sum(fn ($i) => (int) $i->qty * (int) $i->unit_cost)])->save();
        });
        // Phát event cho các module khác bám vào (Procurement → cộng qty_received trên PO; Finance → FIFO layer).
        GoodsReceiptConfirmed::dispatch($doc->refresh()->load('items'));

        return $doc;
    }

    public function confirmTransfer(StockTransfer $doc, int $userId): StockTransfer
    {
        $this->assertDraft($doc->status);
        if ((int) $doc->from_warehouse_id === (int) $doc->to_warehouse_id) {
            throw new RuntimeException('Kho nguồn và kho đích phải khác nhau.');
        }
        $tenantId = (int) $doc->tenant_id;
        $items = $doc->items;
        if ($items->isEmpty()) {
            throw new RuntimeException('Phiếu chưa có dòng hàng nào.');
        }
        DB::transaction(function () use ($doc, $items, $tenantId, $userId) {
            foreach ($items as $it) {
                $qty = (int) $it->qty;
                if ($qty <= 0) {
                    continue;
                }
                $this->ledger->transferOut($tenantId, (int) $it->sku_id, (int) $doc->from_warehouse_id, $qty, 'Chuyển kho '.$doc->code, 'transfer', (int) $doc->getKey(), $userId);
                $this->ledger->transferIn($tenantId, (int) $it->sku_id, (int) $doc->to_warehouse_id, $qty, 'Chuyển kho '.$doc->code, 'transfer', (int) $doc->getKey(), $userId);
            }
            $doc->forceFill(['status' => StockTransfer::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => $userId])->save();
        });

        return $doc->refresh();
    }

    public function confirmStocktake(Stocktake $doc, int $userId): Stocktake
    {
        $this->assertDraft($doc->status);
        $tenantId = (int) $doc->tenant_id;
        $whId = (int) $doc->warehouse_id;
        $items = $doc->items;
        if ($items->isEmpty()) {
            throw new RuntimeException('Phiếu chưa có dòng hàng nào.');
        }
        DB::transaction(function () use ($doc, $items, $tenantId, $whId, $userId) {
            foreach ($items as $it) {
                // re-snapshot system_qty at confirm time, recompute diff against the actual count
                $system = $this->ledger->onHand($tenantId, (int) $it->sku_id, $whId);
                $diff = (int) $it->counted_qty - $system;
                $it->forceFill(['system_qty' => $system, 'diff' => $diff])->save();
                if ($diff !== 0) {
                    $this->ledger->stocktakeAdjust($tenantId, (int) $it->sku_id, $whId, $diff, 'Kiểm kê '.$doc->code, 'stocktake', (int) $doc->getKey(), $userId);
                }
            }
            $doc->forceFill(['status' => Stocktake::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => $userId])->save();
        });

        return $doc->refresh();
    }

    /** Cancel a still-draft phiếu (confirmed phiếu cannot be cancelled — issue a new corrective phiếu instead). */
    public function cancel(GoodsReceipt|StockTransfer|Stocktake $doc): void
    {
        $this->assertDraft($doc->status);
        $doc->forceFill(['status' => 'cancelled'])->save();
    }

    private function assertDraft(string $status): void
    {
        if ($status !== 'draft') {
            throw new RuntimeException($status === 'confirmed' ? 'Phiếu đã xác nhận, không thể thay đổi.' : 'Phiếu đã huỷ.');
        }
    }
}
