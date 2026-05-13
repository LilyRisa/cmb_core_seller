<?php

namespace CMBcoreSeller\Modules\Procurement\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Procurement\Http\Resources\SupplierResource;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use CMBcoreSeller\Modules\Procurement\Models\SupplierPrice;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * /api/v1/suppliers — CRUD nhà cung cấp + bảng giá nhập. SPEC 0014.
 *
 * Permissions: `procurement.view` (đọc), `procurement.manage` (ghi). Soft-delete NCC; bảng giá xoá cứng
 * (lịch sử giá là dữ liệu kế toán — đã giữ trên `purchase_order_items.unit_cost` lúc confirm PO).
 */
class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.view'), 403, 'Bạn không có quyền xem nhà cung cấp.');
        $q = Supplier::query()->withCount('prices');
        if ($term = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
        }
        if ($request->has('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }
        $q->orderBy('name');
        $page = $q->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => SupplierResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.view'), 403, 'Bạn không có quyền xem nhà cung cấp.');
        $supplier = Supplier::query()->with(['prices.sku'])->findOrFail($id);

        return response()->json(['data' => new SupplierResource($supplier)]);
    }

    public function store(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền tạo nhà cung cấp.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'tax_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $tenantId = (int) $tenant->id();
        $supplier = Supplier::query()->create([
            'tenant_id' => $tenantId, 'code' => Supplier::nextCode($tenantId),
            'name' => $data['name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null,
            'tax_code' => $data['tax_code'] ?? null, 'address' => $data['address'] ?? null,
            'payment_terms_days' => $data['payment_terms_days'] ?? 0, 'note' => $data['note'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()->getKey(),
        ]);

        return response()->json(['data' => new SupplierResource($supplier)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền sửa nhà cung cấp.');
        $supplier = Supplier::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'tax_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $supplier->forceFill($data)->save();

        return response()->json(['data' => new SupplierResource($supplier->fresh())]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền xoá nhà cung cấp.');
        $supplier = Supplier::query()->findOrFail($id);
        // Chặn xoá nếu NCC còn PO chưa đóng (`draft`/`confirmed`/`partially_received`).
        $openPoCount = $supplier->purchaseOrders()->whereIn('status', ['draft', 'confirmed', 'partially_received'])->count();
        if ($openPoCount > 0) {
            throw ValidationException::withMessages(['supplier_id' => "NCC còn {$openPoCount} đơn mua chưa đóng — không thể xoá."]);
        }
        $supplier->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    // --- bảng giá nhập (NCC × SKU) ----------------------------------------

    /** POST /api/v1/suppliers/{id}/prices — thêm/sửa giá theo (sku_id, valid_from). Nếu (sku_id, valid_from) tồn tại ⇒ update. */
    public function setPrice(Request $request, int $id, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền cập nhật bảng giá.');
        $supplier = Supplier::query()->findOrFail($id);
        $data = $request->validate([
            'sku_id' => ['required', 'integer'],
            'unit_cost' => ['required', 'integer', 'min:0'],
            'moq' => ['sometimes', 'integer', 'min:1'],
            'currency' => ['sometimes', 'in:VND'],
            'valid_from' => ['sometimes', 'nullable', 'date'],
            'valid_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:valid_from'],
            'is_default' => ['sometimes', 'boolean'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);
        $tenantId = (int) $tenant->id();
        $skuOk = Sku::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->whereKey($data['sku_id'])->exists();
        if (! $skuOk) {
            throw ValidationException::withMessages(['sku_id' => 'SKU không hợp lệ.']);
        }
        // is_default true ⇒ unset các bản is_default khác của cùng (supplier, sku).
        if (! empty($data['is_default'])) {
            SupplierPrice::query()->where('supplier_id', $supplier->getKey())->where('sku_id', $data['sku_id'])
                ->update(['is_default' => false]);
        }
        $price = SupplierPrice::query()->updateOrCreate(
            ['supplier_id' => $supplier->getKey(), 'sku_id' => $data['sku_id'], 'valid_from' => $data['valid_from'] ?? null],
            [
                'tenant_id' => $tenantId, 'unit_cost' => (int) $data['unit_cost'],
                'moq' => (int) ($data['moq'] ?? 1), 'currency' => $data['currency'] ?? 'VND',
                'valid_to' => $data['valid_to'] ?? null, 'is_default' => (bool) ($data['is_default'] ?? false),
                'note' => $data['note'] ?? null,
            ],
        );

        return response()->json(['data' => ['id' => $price->id, 'unit_cost' => (int) $price->unit_cost, 'is_default' => (bool) $price->is_default]], 201);
    }

    public function deletePrice(Request $request, int $id, int $priceId): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền xoá giá.');
        SupplierPrice::query()->where('supplier_id', $id)->whereKey($priceId)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
