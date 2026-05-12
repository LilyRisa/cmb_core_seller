<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Inventory\Http\Resources\InventoryMovementResource;
use CMBcoreSeller\Modules\Inventory\Http\Resources\SkuResource;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** /api/v1/skus — master SKUs. See SPEC 0003 §6. */
class SkuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403, 'Bạn không có quyền xem SKU.');
        $q = Sku::query()->with('levels');
        if ($term = trim((string) $request->query('q', ''))) {
            $q->search($term);
        }
        if ($pid = $request->query('product_id')) {
            $q->where('product_id', (int) $pid);
        }
        if ($request->has('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('low_stock')) {
            $threshold = (int) $request->query('low_stock', 5);
            $q->whereHas('levels', fn ($l) => $l->where('available_cached', '<=', $threshold));
        }
        $q->orderByDesc('id');

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => SkuResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền tạo SKU.');
        $data = $request->validate([
            'sku_code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['sometimes', 'nullable', 'integer'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'cost_price' => ['sometimes', 'integer', 'min:0'],
            'attributes' => ['sometimes', 'array'],
        ]);
        // uniqueness per tenant is enforced by the DB; surface a friendly error.
        if (Sku::query()->where('sku_code', $data['sku_code'])->exists()) {
            return response()->json(['error' => ['code' => 'SKU_CODE_TAKEN', 'message' => 'Mã SKU đã tồn tại.']], 422);
        }
        $sku = Sku::query()->create($data + ['cost_price' => $data['cost_price'] ?? 0]);

        return response()->json(['data' => new SkuResource($sku->load('levels'))], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403, 'Bạn không có quyền xem SKU.');
        $sku = Sku::query()->with(['levels.warehouse', 'mappings.sku'])->findOrFail($id);
        $movements = InventoryMovement::query()->where('sku_id', $sku->getKey())->latest('id')->limit(50)->get();

        return response()->json(['data' => array_merge((new SkuResource($sku))->toArray($request), ['movements' => InventoryMovementResource::collection($movements)])]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa SKU.');
        $sku = Sku::query()->findOrFail($id);
        $data = $request->validate([
            'sku_code' => ['sometimes', 'string', 'max:100', Rule::unique('skus', 'sku_code')->ignore($sku->getKey())->where('tenant_id', $sku->tenant_id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'product_id' => ['sometimes', 'nullable', 'integer'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'cost_price' => ['sometimes', 'integer', 'min:0'],
            'attributes' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $sku->forceFill($data)->save();

        return response()->json(['data' => new SkuResource($sku->load('levels'))]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền xoá SKU.');
        $sku = Sku::query()->findOrFail($id);
        $onHand = (int) $sku->levels()->sum('on_hand');
        $reserved = (int) $sku->levels()->sum('reserved');
        abort_if($onHand !== 0 || $reserved !== 0, 409, 'Không thể xoá SKU còn tồn / đang được giữ.');
        $sku->mappings()->delete();
        $sku->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
