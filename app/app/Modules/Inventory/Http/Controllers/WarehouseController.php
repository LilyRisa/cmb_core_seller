<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Inventory\Http\Resources\WarehouseResource;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** /api/v1/warehouses — stock locations. See SPEC 0003 §6. */
class WarehouseController extends Controller
{
    public function index(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403, 'Bạn không có quyền xem kho.');
        // ensure the tenant always has at least the default warehouse
        Warehouse::defaultFor((int) $tenant->id());
        $warehouses = Warehouse::query()->orderByDesc('is_default')->orderBy('id')->get();

        return response()->json(['data' => WarehouseResource::collection($warehouses)]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403, 'Bạn không có quyền tạo kho.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        if (! empty($data['is_default'])) {
            Warehouse::query()->update(['is_default' => false]);
        }
        $w = Warehouse::query()->create($data);

        return response()->json(['data' => new WarehouseResource($w)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403, 'Bạn không có quyền sửa kho.');
        $w = Warehouse::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        if (! empty($data['is_default'])) {
            Warehouse::query()->where('id', '!=', $w->getKey())->update(['is_default' => false]);
        }
        $w->forceFill($data)->save();

        return response()->json(['data' => new WarehouseResource($w)]);
    }
}
