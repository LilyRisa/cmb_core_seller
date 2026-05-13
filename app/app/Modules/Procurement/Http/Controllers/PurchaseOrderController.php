<?php

namespace CMBcoreSeller\Modules\Procurement\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Procurement\Http\Resources\PurchaseOrderResource;
use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrder;
use CMBcoreSeller\Modules\Procurement\Services\PurchaseOrderService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * /api/v1/purchase-orders — đơn mua hàng. SPEC 0014.
 *
 * - `procurement.view`: index/show.
 * - `procurement.manage`: create/update/confirm/cancel/setPrice (header & giá vốn — kế toán/admin).
 * - `procurement.receive`: tạo phiếu nhập kho từ PO (kho — không sửa giá).
 */
class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.view'), 403, 'Bạn không có quyền xem đơn mua.');
        $q = PurchaseOrder::query()->with(['supplier', 'warehouse'])->withCount('items');
        if ($s = $request->query('status')) {
            $q->whereIn('status', array_filter(array_map('trim', explode(',', (string) $s))));
        }
        if ($sup = $request->query('supplier_id')) {
            $q->where('supplier_id', (int) $sup);
        }
        if ($wh = $request->query('warehouse_id')) {
            $q->where('warehouse_id', (int) $wh);
        }
        if ($term = trim((string) $request->query('q', ''))) {
            $q->where('code', 'like', "%{$term}%");
        }
        $q->orderByDesc('id');
        $page = $q->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => PurchaseOrderResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.view'), 403, 'Bạn không có quyền xem đơn mua.');
        $po = PurchaseOrder::query()->with(['supplier', 'warehouse', 'items.sku', 'goodsReceipts'])->findOrFail($id);

        return response()->json(['data' => new PurchaseOrderResource($po)]);
    }

    public function store(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền tạo đơn mua.');
        $data = $this->validateHeaderAndItems($request);
        try {
            $po = $this->service->create((int) $tenant->id(), $data, $request->user()->getKey());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['purchase_order' => $e->getMessage()]);
        }

        return response()->json(['data' => new PurchaseOrderResource($po->load(['supplier', 'warehouse', 'items.sku']))], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền sửa đơn mua.');
        $po = PurchaseOrder::query()->findOrFail($id);
        $data = $this->validateHeaderAndItems($request, partial: true);
        try {
            $po = $this->service->update($po, $data);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['purchase_order' => $e->getMessage()]);
        }

        return response()->json(['data' => new PurchaseOrderResource($po->load(['supplier', 'warehouse', 'items.sku']))]);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền chốt đơn mua.');
        $po = PurchaseOrder::query()->findOrFail($id);
        try {
            $po = $this->service->confirm($po, $request->user()->getKey());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['purchase_order' => $e->getMessage()]);
        }

        return response()->json(['data' => new PurchaseOrderResource($po->load(['supplier', 'warehouse', 'items.sku']))]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền huỷ đơn mua.');
        $po = PurchaseOrder::query()->findOrFail($id);
        try {
            $po = $this->service->cancel($po, $request->user()->getKey());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['purchase_order' => $e->getMessage()]);
        }

        return response()->json(['data' => new PurchaseOrderResource($po)]);
    }

    /** POST /purchase-orders/{id}/receive — tạo {@see GoodsReceipt} `draft` (FE redirect tới WMS để confirm). */
    public function receive(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.receive') || $request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền nhận hàng theo PO.');
        $po = PurchaseOrder::query()->findOrFail($id);
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sku_id' => ['required', 'integer'],
            'lines.*.qty' => ['required', 'integer', 'min:1'],
            'lines.*.unit_cost' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);
        try {
            $receipt = $this->service->receive($po, $data['lines'], $request->user()->getKey());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['lines' => $e->getMessage()]);
        }

        return response()->json(['data' => [
            'goods_receipt' => [
                'id' => $receipt->id, 'code' => $receipt->code, 'status' => $receipt->status,
                'redirect' => "/inventory?tab=docs&doc=goods-receipts&id={$receipt->id}",
            ],
        ]], 201);
    }

    private function validateHeaderAndItems(Request $request, bool $partial = false): array
    {
        $rules = [
            'supplier_id' => [$partial ? 'sometimes' : 'required', 'integer'],
            'warehouse_id' => [$partial ? 'sometimes' : 'required', 'integer'],
            'expected_at' => ['sometimes', 'nullable', 'date'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'items' => ['sometimes', 'array'],
            'items.*.sku_id' => ['required_with:items', 'integer'],
            'items.*.qty_ordered' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_cost' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'items.*.note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        return $request->validate($rules);
    }
}
