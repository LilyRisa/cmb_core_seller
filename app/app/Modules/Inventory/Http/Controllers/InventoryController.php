<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Inventory\Http\Resources\InventoryLevelResource;
use CMBcoreSeller\Modules\Inventory\Http\Resources\InventoryMovementResource;
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
