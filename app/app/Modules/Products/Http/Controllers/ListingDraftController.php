<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Products\Http\Requests\BulkUpdateListingDraftRequest;
use CMBcoreSeller\Modules\Products\Http\Requests\CloneListingDraftRequest;
use CMBcoreSeller\Modules\Products\Http\Requests\StoreListingDraftRequest;
use CMBcoreSeller\Modules\Products\Http\Requests\UpdateListingDraftRequest;
use CMBcoreSeller\Modules\Products\Http\Resources\ListingDraftResource;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use CMBcoreSeller\Modules\Products\Services\ProductDescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thin controller for the marketplace listing-draft editor: create a draft from
 * a master product, read it back, and apply edits (which trigger revalidation).
 */
final class ListingDraftController extends Controller
{
    public function __construct(private ListingDraftService $svc) {}

    public function store(StoreListingDraftRequest $request, int $productId): JsonResponse
    {
        $draft = $this->svc->createDraft(
            $productId,
            (int) $request->validated('channel_account_id'),
            (string) $request->validated('provider'),
        );

        return (new ListingDraftResource($draft))->response()->setStatusCode(201);
    }

    /** GET /api/v1/listings/bulk?ids=1,2,3 — nhiều nháp đầy đủ, phải CÙNG provider. */
    public function bulkShow(Request $request): JsonResponse
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))));
        abort_if($ids === [], 422, 'Thiếu danh sách ids.');

        $drafts = ListingDraft::with(['product', 'skus.masterSku'])->whereIn('id', $ids)->get();
        abort_if($drafts->pluck('provider')->unique()->count() > 1, 422, 'Chỉ chọn được các listing cùng 1 sàn.');

        return response()->json(['data' => ListingDraftResource::collection($drafts)]);
    }

    /** PUT /api/v1/listings/bulk — lưu nhiều nháp, mỗi nháp xử lý độc lập. */
    public function bulkUpdate(BulkUpdateListingDraftRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->svc->bulkUpdate($request->validated('items'))]);
    }

    public function show(int $id): ListingDraftResource
    {
        $draft = ListingDraft::with(['product', 'skus.masterSku'])->findOrFail($id);

        return new ListingDraftResource($draft);
    }

    public function update(UpdateListingDraftRequest $request, int $id): ListingDraftResource
    {
        $draft = $this->svc->update($id, $request->validated());

        return new ListingDraftResource($draft);
    }

    public function aiDescription(int $id, ProductDescriptionService $ai): JsonResponse
    {
        $draft = ListingDraft::findOrFail($id);

        return response()->json(['data' => $ai->suggest($draft)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $draft = ListingDraft::findOrFail($id);
        $draft->skus()->delete();
        $draft->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function cloneTo(CloneListingDraftRequest $request, int $id): JsonResponse
    {
        $draft = $this->svc->cloneToChannel(
            $id,
            (int) $request->validated('channel_account_id'),
        );

        return (new ListingDraftResource($draft))->response()->setStatusCode(201);
    }
}
