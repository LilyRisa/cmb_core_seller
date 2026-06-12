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
        $q = Product::query()->withCount('skus')->with('listingDrafts');
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
            // Các field "giàu" do extension copy sản phẩm gửi (Shopee/Lazada/TikTok/AliExpress).
            // Product không có cột riêng cho chúng ⇒ gộp vào `meta` (cột JSON) để KHÔNG mất dữ liệu.
            // Tất cả optional+nullable nên KHÔNG ảnh hưởng luồng tạo product của SPA (chỉ gửi name/image/brand/category/meta).
            'description' => ['sometimes', 'nullable', 'string'],
            'unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'thumbnail_img' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'image_links' => ['sometimes', 'array'],
            'image_links.*' => ['string', 'max:1000'],
            'video_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'source' => ['sometimes', 'nullable', 'string', 'max:50'],
            'source_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'variants' => ['sometimes', 'array'],
            'variants.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['sometimes', 'nullable', 'string', 'max:191'],
            'variants.*.image' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);
        $product = Product::query()->create($this->productAttributes($data));

        return response()->json(['data' => new ProductResource($product->loadCount('skus'))], 201);
    }

    /**
     * Map payload về cột của Product. `name/image/brand/category` giữ nguyên;
     * mọi field "giàu" từ extension copy được gộp vào `meta` (cột JSON) để không
     * mất dữ liệu. Thiếu `image` mà có `thumbnail_img` thì dùng thumbnail làm ảnh
     * đại diện. Luồng SPA (không gửi field giàu) ⇒ `meta` giữ nguyên như cũ.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function productAttributes(array $data): array
    {
        $richKeys = ['description', 'unit_price', 'unit', 'thumbnail_img', 'image_links', 'video_url', 'source', 'source_url', 'variants'];

        /** @var array<string,mixed> $meta */
        $meta = $data['meta'] ?? [];
        foreach ($richKeys as $key) {
            if (array_key_exists($key, $data)) {
                $meta[$key] = $data[$key];
            }
        }

        $attrs = ['name' => $data['name']];
        foreach (['image', 'brand', 'category'] as $key) {
            if (array_key_exists($key, $data)) {
                $attrs[$key] = $data[$key];
            }
        }
        if (empty($attrs['image']) && ! empty($data['thumbnail_img'])) {
            $attrs['image'] = $data['thumbnail_img'];
        }
        if ($meta !== []) {
            $attrs['meta'] = $meta;
        }

        return $attrs;
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem sản phẩm.');

        return response()->json(['data' => new ProductResource(Product::query()->withCount('skus')->with('listingDrafts')->findOrFail($id))]);
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
