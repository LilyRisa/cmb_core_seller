<?php

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Products\Http\Resources\ProductResource;
use CMBcoreSeller\Modules\Products\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** /api/v1/products — base products. SKUs (Inventory module) reference these. See SPEC 0003 §6. */
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem sản phẩm.');
        $q = Product::query()->withCount('skus');
        if ($term = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%"));
        }
        $q->orderByDesc('id');
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => ProductResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền tạo sản phẩm.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta' => ['sometimes', 'array'],
        ]);
        $product = Product::query()->create($data);

        return response()->json(['data' => new ProductResource($product->loadCount('skus'))], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem sản phẩm.');

        return response()->json(['data' => new ProductResource(Product::query()->withCount('skus')->findOrFail($id))]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa sản phẩm.');
        $product = Product::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'image' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta' => ['sometimes', 'array'],
        ]);
        $product->forceFill($data)->save();

        return response()->json(['data' => new ProductResource($product->loadCount('skus'))]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền xoá sản phẩm.');
        Product::query()->findOrFail($id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
