<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Products\Http\Requests\SetPromotionSkusRequest;
use CMBcoreSeller\Modules\Products\Http\Requests\StorePromotionRequest;
use CMBcoreSeller\Modules\Products\Http\Requests\UpdatePromotionRequest;
use CMBcoreSeller\Modules\Products\Http\Resources\ChannelPromotionResource;
use CMBcoreSeller\Modules\Products\Jobs\PushPromotionJob;
use CMBcoreSeller\Modules\Products\Models\ChannelPromotion;
use CMBcoreSeller\Modules\Products\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chiến dịch giảm giá nhiều SKU. 2 tab: `tab=pushed` (đã/đang trên sàn) | `tab=draft`.
 * Controller mỏng: Request → PromotionService → Resource. KHÔNG đụng luồng listing.
 */
final class PromotionController extends Controller
{
    public function __construct(private PromotionService $svc) {}

    public function index(Request $r): JsonResponse
    {
        abort_unless($r->user()?->can('products.view'), 403, 'Bạn không có quyền xem chiến dịch.');

        $q = ChannelPromotion::query()->withCount('skus')->orderByDesc('id');
        if ($cid = $r->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        // 2 tab: pushed = đang/đã trên sàn; draft = nháp chờ đẩy (+ lỗi để sửa lại).
        $tab = (string) $r->query('tab', 'pushed');
        $q->whereIn('status', $tab === 'draft'
            ? [ChannelPromotion::STATUS_DRAFT, ChannelPromotion::STATUS_FAILED]
            : [ChannelPromotion::STATUS_PUSHING, ChannelPromotion::STATUS_LIVE, ChannelPromotion::STATUS_ENDED]);

        return response()->json(['data' => ChannelPromotionResource::collection($q->get())]);
    }

    public function show(Request $r, int $id): JsonResponse
    {
        abort_unless($r->user()?->can('products.view'), 403, 'Bạn không có quyền xem chiến dịch.');
        $promo = ChannelPromotion::query()->with(['skus.channelListing'])->findOrFail($id);

        return response()->json(['data' => new ChannelPromotionResource($promo)]);
    }

    public function store(StorePromotionRequest $r): JsonResponse
    {
        abort_unless($r->user()?->can('products.manage'), 403, 'Bạn không có quyền tạo chiến dịch.');
        $promo = $this->svc->createDraft((int) $r->validated('channel_account_id'), $r->validated(), (int) $r->user()->getAuthIdentifier());

        return (new ChannelPromotionResource($promo))->response()->setStatusCode(201);
    }

    public function update(UpdatePromotionRequest $r, int $id): JsonResponse
    {
        abort_unless($r->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa chiến dịch.');
        $promo = ChannelPromotion::query()->findOrFail($id);

        return response()->json(['data' => new ChannelPromotionResource($this->svc->updateDraft($promo, $r->validated()))]);
    }

    public function setSkus(SetPromotionSkusRequest $r, int $id): JsonResponse
    {
        abort_unless($r->user()?->can('products.manage'), 403, 'Bạn không có quyền sửa chiến dịch.');
        $promo = ChannelPromotion::query()->findOrFail($id);
        $promo = $this->svc->setSkus($promo, (array) $r->validated('skus'));

        return response()->json(['data' => new ChannelPromotionResource($promo->load(['skus.channelListing']))]);
    }

    public function push(Request $r, int $id): JsonResponse
    {
        abort_unless($r->user()?->can('products.manage'), 403, 'Bạn không có quyền đẩy chiến dịch.');
        $promo = ChannelPromotion::query()->with('skus')->findOrFail($id);
        abort_if($promo->skus->isEmpty(), 422, 'Chiến dịch chưa có SKU nào.');

        // Chặn chồng lấn: SKU đã thuộc chiến dịch đang chạy/sắp chạy khác.
        $conflict = $this->svc->conflictingSkuIds($promo);
        abort_if($conflict !== [], 422, 'Có SKU đang thuộc chiến dịch khác: '.implode(', ', array_slice($conflict, 0, 10)));

        PushPromotionJob::dispatch((int) $promo->getKey());

        return response()->json(['data' => ['queued' => true]]);
    }

    public function end(Request $r, int $id): JsonResponse
    {
        abort_unless($r->user()?->can('products.manage'), 403, 'Bạn không có quyền kết thúc chiến dịch.');
        $promo = ChannelPromotion::query()->with('skus')->findOrFail($id);
        $this->svc->endOnChannel($promo);

        return response()->json(['data' => new ChannelPromotionResource($promo->fresh() ?? $promo)]);
    }

    public function destroy(Request $r, int $id): JsonResponse
    {
        abort_unless($r->user()?->can('products.manage'), 403, 'Bạn không có quyền xóa chiến dịch.');
        $promo = ChannelPromotion::query()->findOrFail($id);
        $promo->skus()->delete();
        $promo->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** SKU/sản phẩm đang bận (đã có chương trình đang/sắp chạy) trong gian hàng — FE tô xám + hiện giá giảm. */
    public function busySkus(Request $r): JsonResponse
    {
        abort_unless($r->user()?->can('products.view'), 403, 'Bạn không có quyền.');
        $cid = (int) $r->query('channel_account_id');
        $except = $r->query('except') !== null ? (int) $r->query('except') : null;
        // `prices`: khoá (external_sku_id hoặc external_product_id cho item no-variant) → giá giảm đang chạy (VND).
        $prices = $this->svc->busyPromoPrices($cid, $except);
        // strval BẮT BUỘC: PHP ép key mảng dạng chuỗi-số thành int ⇒ json_encode ra SỐ. FE so khớp Set với
        // external_sku_id (chuỗi) ⇒ số≠chuỗi, SKU sàn (id toàn số: TikTok/Shopee) KHÔNG bao giờ bị tô xám.
        $ids = array_map('strval', array_keys($prices));

        return response()->json(['data' => ['external_sku_ids' => $ids, 'prices' => (object) $prices]]);
    }

    /** Đồng bộ chiến dịch đang có trên sàn về app (tab "đã đẩy"). */
    public function sync(Request $r): JsonResponse
    {
        abort_unless($r->user()?->can('products.manage'), 403, 'Bạn không có quyền đồng bộ.');
        $count = $this->svc->syncFromChannel((int) $r->query('channel_account_id'));

        return response()->json(['data' => ['synced' => $count]]);
    }

    /** Năng lực giảm giá của sàn (render UI khớp sàn). */
    public function capabilities(Request $r): JsonResponse
    {
        abort_unless($r->user()?->can('products.view'), 403, 'Bạn không có quyền.');
        try {
            return response()->json(['data' => $this->svc->capabilities((string) $r->query('provider'))]);
        } catch (UnsupportedOperation) {
            return response()->json(['data' => null]);
        }
    }
}
