<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Inventory\Http\Resources\InventoryLevelResource;
use CMBcoreSeller\Modules\Inventory\Http\Resources\InventoryMovementResource;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /api/v1/inventory — stock levels per (SKU, warehouse), manual adjustments, and
 * the immutable movements ledger. See SPEC 0003 §6.
 */
class InventoryController extends Controller
{
    /** GET /api/v1/inventory/levels */
    public function levels(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403, 'Bạn không có quyền xem tồn kho.');

        $q = InventoryLevel::query()->with(['sku', 'warehouse']);
        if ($sid = $request->query('sku_id')) {
            $q->where('sku_id', (int) $sid);
        }
        if ($wid = $request->query('warehouse_id')) {
            $q->where('warehouse_id', (int) $wid);
        }
        if ($request->boolean('negative')) {
            $q->where('is_negative', true);
        }
        if ($request->filled('low_stock')) {
            $q->where('available_cached', '<=', (int) $request->query('low_stock', 5));
        }
        $q->orderBy('sku_id')->orderBy('warehouse_id');

        return $this->paginated($request, $q, fn ($c) => InventoryLevelResource::collection($c));
    }

    /** POST /api/v1/inventory/adjust  { sku_id, warehouse_id?, qty_change, note? } */
    public function adjust(Request $request, InventoryLedgerService $ledger, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403, 'Bạn không có quyền điều chỉnh tồn kho.');
        $data = $request->validate([
            'sku_id' => ['required', 'integer'],
            'warehouse_id' => ['sometimes', 'nullable', 'integer'],
            'qty_change' => ['required', 'integer', 'not_in:0'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $sku = Sku::query()->findOrFail($data['sku_id']);   // 404 if not this tenant's

        $movement = $ledger->adjust(
            (int) $tenant->id(), (int) $sku->getKey(), $data['warehouse_id'] ?? null,
            (int) $data['qty_change'], $data['note'] ?? null, $request->user()->getKey(),
        );

        return response()->json(['data' => new InventoryMovementResource($movement)], 201);
    }

    /**
     * POST /api/v1/inventory/bulk-adjust — apply many (SKU, qty) lines at once
     * ("phiếu nhập/xuất kho thủ công hàng loạt"). Each line → one inventory_movements
     * row sharing the same `note` (no header table — Phase 5 adds that). See SPEC 0004 §3.1.
     */
    public function bulkAdjust(Request $request, InventoryLedgerService $ledger, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403, 'Bạn không có quyền điều chỉnh tồn kho.');
        $data = $request->validate([
            'kind' => ['required', 'in:goods_receipt,manual_adjust'],
            'warehouse_id' => ['sometimes', 'nullable', 'integer'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1', 'max:500'],
            'lines.*.sku_id' => ['required', 'integer'],
            'lines.*.qty_change' => ['required', 'integer', 'not_in:0'],
        ]);
        $skuIds = array_map(fn ($l) => (int) $l['sku_id'], $data['lines']);
        if (count($skuIds) !== count(array_unique($skuIds))) {
            return response()->json(['error' => ['code' => 'DUPLICATE_SKU', 'message' => 'Một SKU chỉ được xuất hiện một lần trong phiếu — vui lòng gộp lại.']], 422);
        }
        $valid = Sku::query()->whereIn('id', $skuIds)->pluck('id')->all();
        $missing = array_diff($skuIds, array_map('intval', $valid));
        if ($missing !== []) {
            return response()->json(['error' => ['code' => 'SKU_NOT_FOUND', 'message' => 'SKU không tồn tại: '.implode(', ', $missing)]], 422);
        }
        if ($data['kind'] === 'goods_receipt') {
            foreach ($data['lines'] as $l) {
                if ((int) $l['qty_change'] <= 0) {
                    return response()->json(['error' => ['code' => 'INVALID_QTY', 'message' => 'Phiếu nhập kho chỉ nhận số lượng dương.']], 422);
                }
            }
        }

        $tenantId = (int) $tenant->id();
        $userId = $request->user()->getKey();
        $note = $data['note'] ?? null;
        $whId = $data['warehouse_id'] ?? null;
        $movements = [];
        foreach ($data['lines'] as $l) {
            $movements[] = $data['kind'] === 'goods_receipt'
                ? $ledger->receipt($tenantId, (int) $l['sku_id'], $whId, (int) $l['qty_change'], $note, 'manual_bulk', null, $userId)
                : $ledger->adjust($tenantId, (int) $l['sku_id'], $whId, (int) $l['qty_change'], $note, $userId, 'manual_bulk', null);
        }

        return response()->json(['data' => ['applied' => count($movements), 'movements' => InventoryMovementResource::collection($movements)]], 201);
    }

    /** POST /api/v1/inventory/push-stock { sku_ids } — manually re-push stock for the selected SKUs now (no debounce). */
    public function pushStock(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền đẩy tồn.');
        $data = $request->validate(['sku_ids' => ['required', 'array', 'min:1', 'max:500'], 'sku_ids.*' => ['integer']]);
        $ids = Sku::query()->whereIn('id', array_map('intval', $data['sku_ids']))->pluck('id');
        foreach ($ids as $skuId) {
            PushStockForSku::dispatch((int) $tenant->id(), (int) $skuId);
        }

        return response()->json(['data' => ['queued' => $ids->count()]]);
    }

    /** GET /api/v1/inventory/movements */
    public function movements(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403, 'Bạn không có quyền xem sổ cái tồn kho.');

        $q = InventoryMovement::query()->latest('id');
        if ($sid = $request->query('sku_id')) {
            $q->where('sku_id', (int) $sid);
        }
        if ($wid = $request->query('warehouse_id')) {
            $q->where('warehouse_id', (int) $wid);
        }
        if ($type = $request->query('type')) {
            $q->whereIn('type', array_map('trim', explode(',', (string) $type)));
        }
        if ($rt = $request->query('ref_type')) {
            $q->where('ref_type', (string) $rt);
            if ($rid = $request->query('ref_id')) {
                $q->where('ref_id', (int) $rid);
            }
        }

        return $this->paginated($request, $q, fn ($c) => InventoryMovementResource::collection($c));
    }

    private function paginated(Request $request, $query, callable $resource): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $resource($page->getCollection()),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }
}
