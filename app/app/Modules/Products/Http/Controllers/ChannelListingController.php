<?php

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\FetchChannelListings;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Http\Requests\UpdateMarketplaceListingRequest;
use CMBcoreSeller\Modules\Products\Http\Resources\ChannelListingResource;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Products\Services\MarketplaceCloneService;
use CMBcoreSeller\Modules\Products\Services\MarketplaceListingEditService;
use CMBcoreSeller\Modules\Products\Services\ProductDescriptionService;
use CMBcoreSeller\Modules\Products\Services\PromotionService;
use CMBcoreSeller\Support\SkuSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** /api/v1/channel-listings — products/variants on connected shops + their SKU mappings. See SPEC 0003 §6. */
class ChannelListingController extends Controller
{
    public function index(Request $request, PromotionService $promotions): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem listing.');
        $q = $this->applyFilters($request, $promotions)->withCount('mappings')->with('mappings.sku');
        $q->orderByDesc('id');
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => ChannelListingResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    /**
     * GET /api/v1/channel-listings/grouped — như `index()` nhưng phân trang THEO SẢN PHẨM (SPEC 2026-07-14):
     * gộp các dòng biến thể cùng `channel_account_id`+`external_product_id` (hoặc chính `id` nếu không có
     * external_product_id — coi là nhóm 1 phần tử) thành 1 phần tử, `variant_count`/`variants[]` đính kèm.
     */
    public function grouped(Request $request, PromotionService $promotions): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem listing.');

        $groupKeyExpr = 'COALESCE(external_product_id, CAST(id AS VARCHAR))';
        $groupsQuery = $this->applyFilters($request, $promotions)
            ->selectRaw("channel_account_id, {$groupKeyExpr} as group_key, MAX(id) as sort_id, COUNT(*) as variant_count")
            ->groupBy('channel_account_id', DB::raw($groupKeyExpr))
            ->orderByDesc('sort_id');

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $groupsPage = $groupsQuery->paginate($perPage)->appends($request->query());

        $pairs = $groupsPage->getCollection()->map(fn ($g) => [(int) $g->channel_account_id, (string) $g->getAttribute('group_key')])->all();

        $rowsByGroup = collect();
        if ($pairs !== []) {
            $rowsByGroup = $this->applyFilters($request, $promotions)
                ->with('mappings.sku')
                ->where(function ($w) use ($pairs, $groupKeyExpr) {
                    foreach ($pairs as [$cid, $key]) {
                        $w->orWhere(fn ($x) => $x->where('channel_account_id', $cid)->whereRaw("{$groupKeyExpr} = ?", [$key]));
                    }
                })
                ->get()
                ->groupBy(fn ($r) => $r->channel_account_id.'|'.($r->external_product_id ?? (string) $r->id));
        }

        $data = $groupsPage->getCollection()->map(function ($g) use ($rowsByGroup, $request) {
            $key = $g->channel_account_id.'|'.$g->getAttribute('group_key');
            $variants = $rowsByGroup->get($key, collect());
            $first = $variants->first();

            return [
                'channel_account_id' => (int) $g->channel_account_id,
                'external_product_id' => $first?->external_product_id,
                'title' => $first?->title,
                'image' => $first?->image,
                'variant_count' => (int) $g->getAttribute('variant_count'),
                'variants' => ChannelListingResource::collection($variants->values())->resolve($request),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => ['pagination' => ['page' => $groupsPage->currentPage(), 'per_page' => $groupsPage->perPage(), 'total' => $groupsPage->total(), 'total_pages' => $groupsPage->lastPage()]],
        ]);
    }

    /**
     * Bộ filter dùng chung `index()`/`grouped()` — KHÔNG áp orderBy/pagination/eager-load (caller tự thêm).
     *
     * @return Builder<ChannelListing>
     */
    private function applyFilters(Request $request, PromotionService $promotions): Builder
    {
        $q = ChannelListing::query();
        if ($cid = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        // Lọc bỏ SKU đang BẬN (đã có khuyến mãi đang/sắp chạy) — SERVER-SIDE để "Chỉ hiện SKU chọn được" của
        // picker chiến dịch giảm giá đúng qua mọi trang (trước lọc client-side trên từng trang ⇒ sai). Cần 1
        // gian hàng cụ thể; `except` = chiến dịch đang sửa (không tự loại SKU của nó).
        if ($request->boolean('exclude_busy') && $cid) {
            $except = $request->query('except') !== null ? (int) $request->query('except') : null;
            // strval: external_sku_id/product_id là cột chuỗi; tránh phụ thuộc ép kiểu ngầm của DB khi so khớp.
            $busy = array_map('strval', array_keys($promotions->busyPromoPrices((int) $cid, $except)));
            if ($busy !== []) {
                $q->whereNotIn('external_sku_id', $busy)
                    ->where(fn ($x) => $x->whereNotIn('external_product_id', $busy)->orWhereNull('external_product_id'));
            }
        }
        // Lọc nhiều gian hàng (multi-select) — CSV id; bỏ qua nếu rỗng.
        if ($ids = $request->query('channel_account_ids')) {
            $list = array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
            if ($list !== []) {
                $q->whereIn('channel_account_id', $list);
            }
        }
        if ($status = $request->query('sync_status')) {
            $q->where('sync_status', (string) $status);
        }
        if ($request->has('mapped')) {
            $request->boolean('mapped') ? $q->whereHas('mappings') : $q->unmapped();
        }
        if ($term = trim((string) $request->query('q', ''))) {
            SkuSearch::apply($q, $term, ['seller_sku', 'external_sku_id'], ['title']);
        }

        return $q;
    }

    /** POST /api/v1/channel-listings/sync — pull listings from every active shop that supports it, then auto-match. */
    public function sync(Request $request, ChannelRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền đồng bộ listing.');
        $n = 0;
        ChannelAccount::query()->active()->orderBy('id')->each(function (ChannelAccount $a) use ($registry, &$n) {
            if ($registry->has($a->provider) && $registry->for($a->provider)->supports('listings.fetch')) {
                FetchChannelListings::dispatch((int) $a->getKey());
                $n++;
            }
        });

        return response()->json(['data' => ['queued' => $n]]);
    }

    /** PATCH /api/v1/channel-listings/{id}  { is_stock_locked? } — pin/unpin auto-push. */
    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền sửa listing.');
        $listing = ChannelListing::query()->findOrFail($id);
        $data = $request->validate(['is_stock_locked' => ['sometimes', 'boolean']]);
        $listing->forceFill($data)->save();

        return response()->json(['data' => new ChannelListingResource($listing->loadCount('mappings')->load('mappings.sku'))]);
    }

    /**
     * GET /api/v1/channel-listings/{id}/marketplace-detail — full editable content
     * (title/description/images/per-SKU price) fetched live from the marketplace.
     */
    public function marketplaceDetail(Request $request, int $id, MarketplaceListingEditService $svc): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa sản phẩm.');
        $listing = ChannelListing::query()->findOrFail($id);

        return response()->json(['data' => $svc->detail($listing)]);
    }

    /**
     * PUT /api/v1/channel-listings/{id}/marketplace — push title/description/images/
     * per-SKU price back to the marketplace (stock excluded — pushed via SKU sync).
     */
    public function marketplaceUpdate(UpdateMarketplaceListingRequest $request, int $id, MarketplaceListingEditService $svc): JsonResponse
    {
        $listing = ChannelListing::query()->findOrFail($id);

        return response()->json(['data' => $svc->update($listing, $request->validated())]);
    }

    /**
     * POST /api/v1/channel-listings/{id}/ai-description — gợi ý mô tả bằng AI cho sản phẩm
     * đã có trên sàn. Nhận `description` tùy chọn (mô tả đang soạn) để AI cải thiện.
     */
    public function aiDescription(Request $request, int $id, ProductDescriptionService $ai): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa sản phẩm.');
        $listing = ChannelListing::query()->findOrFail($id);
        $current = $request->input('description');

        return response()->json(['data' => $ai->suggestForListing($listing, is_string($current) ? $current : null)]);
    }

    /**
     * POST /api/v1/channel-listings/{id}/clone-to-shops — sao chép sản phẩm đã có trên
     * sàn sang nhiều shop. Cùng nền tảng ⇒ nháp READY (đẩy được luôn); khác ⇒ DRAFT (cần sửa).
     */
    public function cloneToShops(Request $request, int $id, MarketplaceCloneService $svc): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sao chép sản phẩm.');
        $data = $request->validate([
            'channel_account_ids' => ['required', 'array', 'min:1', 'max:50'],
            'channel_account_ids.*' => ['integer'],
        ]);

        return response()->json(['data' => $svc->cloneToShops($id, $data['channel_account_ids'])], 201);
    }

    /**
     * POST /api/v1/channel-listings/bulk-clone-to-shops — sao chép NHIỀU sản phẩm "đã có trên sàn" sang
     * nhiều shop cùng lúc (SPEC 2026-07-14). Mỗi channel_listing_id đại diện 1 sản phẩm (giống clone đơn).
     */
    public function bulkCloneToShops(Request $request, MarketplaceCloneService $svc): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403, 'Bạn không có quyền sao chép sản phẩm.');
        $data = $request->validate([
            'channel_listing_ids' => ['required', 'array', 'min:1', 'max:50'],
            'channel_listing_ids.*' => ['integer'],
            'channel_account_ids' => ['required', 'array', 'min:1', 'max:50'],
            'channel_account_ids.*' => ['integer'],
        ]);

        return response()->json(['data' => $svc->bulkCloneToShops($data['channel_listing_ids'], $data['channel_account_ids'])], 201);
    }
}
