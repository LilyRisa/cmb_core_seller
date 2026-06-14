<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Products\Http\Requests\CloneListingDraftRequest;
use CMBcoreSeller\Modules\Products\Http\Requests\StoreListingDraftRequest;
use CMBcoreSeller\Modules\Products\Http\Requests\UpdateListingDraftRequest;
use CMBcoreSeller\Modules\Products\Http\Resources\ListingDraftResource;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use Illuminate\Http\JsonResponse;

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
