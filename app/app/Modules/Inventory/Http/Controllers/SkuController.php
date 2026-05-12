<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Http\Resources\InventoryMovementResource;
use CMBcoreSeller\Modules\Inventory\Http\Resources\SkuResource;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Inventory\Services\SkuMappingService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** /api/v1/skus — master SKUs (PIM core). See SPEC 0003 §6 and SPEC 0005. */
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

    /**
     * Create a master SKU. Accepts the catalogue fields plus, optionally, channel-SKU
     * links (`mappings`) and opening stock per warehouse (`levels`) so the "Thêm SKU
     * đơn độc" form can do everything in one round-trip. See SPEC 0005 §5–§6.
     */
    public function store(Request $request, InventoryLedgerService $ledger, SkuMappingService $mappingService, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền tạo SKU.');
        $data = $request->validate([
            'sku_code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['sometimes', 'nullable', 'integer'],
            'spu_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category' => ['sometimes', 'nullable', 'string', 'max:120'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'gtins' => ['sometimes', 'nullable', 'array', 'max:10'],
            'gtins.*' => ['string', 'max:64'],
            'base_unit' => ['sometimes', 'nullable', 'string', 'max:16'],
            'cost_price' => ['sometimes', 'integer', 'min:0'],
            'ref_sale_price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'sale_start_date' => ['sometimes', 'nullable', 'date'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'weight_grams' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'length_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'width_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'height_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'attributes' => ['sometimes', 'array'],
            'mappings' => ['sometimes', 'array'],
            'mappings.*.channel_account_id' => ['required_with:mappings', 'integer'],
            'mappings.*.external_sku_id' => ['required_with:mappings', 'string', 'max:191'],
            'mappings.*.seller_sku' => ['sometimes', 'nullable', 'string', 'max:191'],
            'mappings.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'levels' => ['sometimes', 'array'],
            'levels.*.warehouse_id' => ['required_with:levels', 'integer'],
            'levels.*.on_hand' => ['sometimes', 'integer', 'min:0'],
            'levels.*.cost_price' => ['sometimes', 'integer', 'min:0'],
        ]);
        if (Sku::query()->where('sku_code', $data['sku_code'])->exists()) {
            return response()->json(['error' => ['code' => 'SKU_CODE_TAKEN', 'message' => 'Mã SKU đã tồn tại.']], 422);
        }
        $tenantId = (int) $tenant->id();
        $userId = $request->user()->getKey();

        $accountIds = collect($data['mappings'] ?? [])->pluck('channel_account_id')->filter()->unique()->values();
        if ($accountIds->isNotEmpty() && ChannelAccount::query()->whereIn('id', $accountIds->all())->count() !== $accountIds->count()) {
            throw ValidationException::withMessages(['mappings' => 'Gian hàng không hợp lệ.']);
        }
        $warehouseIds = collect($data['levels'] ?? [])->pluck('warehouse_id')->filter()->unique()->values();
        if ($warehouseIds->isNotEmpty() && Warehouse::query()->whereIn('id', $warehouseIds->all())->count() !== $warehouseIds->count()) {
            throw ValidationException::withMessages(['levels' => 'Kho không hợp lệ.']);
        }

        /** @var Sku $sku */
        $sku = DB::transaction(function () use ($data, $tenantId, $userId, $ledger, $mappingService) {
            $sku = Sku::query()->create([
                'tenant_id' => $tenantId,
                'product_id' => $data['product_id'] ?? null,
                'spu_code' => $data['spu_code'] ?? null,
                'category' => $data['category'] ?? null,
                'sku_code' => $data['sku_code'],
                'barcode' => $data['barcode'] ?? null,
                'gtins' => $data['gtins'] ?? null,
                'name' => $data['name'],
                'base_unit' => ($data['base_unit'] ?? null) ?: 'PCS',
                'cost_price' => $data['cost_price'] ?? 0,
                'ref_sale_price' => $data['ref_sale_price'] ?? null,
                'sale_start_date' => $data['sale_start_date'] ?? null,
                'note' => $data['note'] ?? null,
                'weight_grams' => $data['weight_grams'] ?? null,
                'length_cm' => $data['length_cm'] ?? null,
                'width_cm' => $data['width_cm'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'attributes' => $data['attributes'] ?? null,
                'is_active' => true,
            ]);

            foreach (($data['mappings'] ?? []) as $m) {
                /** @var array<string,mixed> $m */
                $listing = ChannelListing::query()->firstOrCreate(
                    ['channel_account_id' => (int) $m['channel_account_id'], 'external_sku_id' => (string) $m['external_sku_id']],
                    ['tenant_id' => $tenantId, 'seller_sku' => $m['seller_sku'] ?? null, 'title' => null, 'currency' => 'VND', 'is_active' => true],
                );
                $mappingService->setMapping($tenantId, $listing, 'single', [['sku_id' => $sku->getKey(), 'quantity' => (int) ($m['quantity'] ?? 1)]], $userId);
            }

            foreach (($data['levels'] ?? []) as $lvl) {
                /** @var array<string,mixed> $lvl */
                $wid = (int) $lvl['warehouse_id'];
                $onHand = (int) ($lvl['on_hand'] ?? 0);
                $cost = isset($lvl['cost_price']) ? (int) $lvl['cost_price'] : null;
                if ($onHand > 0) {
                    $ledger->receipt($tenantId, $sku->getKey(), $wid, $onHand, 'Tồn đầu kỳ', 'sku_create', $sku->getKey(), $userId);
                }
                if ($onHand > 0 || $cost !== null) {
                    $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
                        ->firstOrCreate(['tenant_id' => $tenantId, 'sku_id' => $sku->getKey(), 'warehouse_id' => $wid]);
                    $level->update(['cost_price' => $cost ?? $level->cost_price]);
                }
            }

            return $sku;
        });

        return response()->json(['data' => new SkuResource($sku->load('levels.warehouse', 'mappings.channelListing'))], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403, 'Bạn không có quyền xem SKU.');
        $sku = Sku::query()->with(['levels.warehouse', 'mappings.sku', 'mappings.channelListing'])->findOrFail($id);
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
            'spu_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category' => ['sometimes', 'nullable', 'string', 'max:120'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'gtins' => ['sometimes', 'nullable', 'array', 'max:10'],
            'gtins.*' => ['string', 'max:64'],
            'base_unit' => ['sometimes', 'string', 'max:16'],
            'cost_price' => ['sometimes', 'integer', 'min:0'],
            'ref_sale_price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'sale_start_date' => ['sometimes', 'nullable', 'date'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'weight_grams' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'length_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'width_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'height_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'attributes' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $sku->forceFill($data)->save();

        return response()->json(['data' => new SkuResource($sku->load('levels'))]);
    }

    /** POST /api/v1/skus/{id}/image — upload (replace) the SKU image to the media disk (R2). SPEC 0005 §7. */
    public function uploadImage(Request $request, int $id, MediaUploader $uploader): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa SKU.');
        $mimes = implode(',', (array) config('media.images.mimes', ['jpg', 'jpeg', 'png', 'webp']));
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:'.$mimes, 'max:'.(int) config('media.images.max_kb', 5120)],
        ]);
        $sku = Sku::query()->findOrFail($id);
        $old = $sku->image_path;
        $stored = $uploader->storeImage($request->file('image'), (int) $sku->tenant_id, 'skus');
        $sku->forceFill(['image_url' => $stored['url'], 'image_path' => $stored['path']])->save();
        if ($old && $old !== $stored['path']) {
            $uploader->delete($old);
        }

        return response()->json(['data' => new SkuResource($sku->load('levels'))]);
    }

    /** DELETE /api/v1/skus/{id}/image — remove the SKU image. */
    public function deleteImage(Request $request, int $id, MediaUploader $uploader): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa SKU.');
        $sku = Sku::query()->findOrFail($id);
        $path = $sku->image_path;
        $sku->forceFill(['image_url' => null, 'image_path' => null])->save();
        $uploader->delete($path);

        return response()->json(['data' => ['deleted' => true]]);
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
