<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Requests\AdDraftRequest;
use CMBcoreSeller\Modules\Marketing\Http\Resources\AdDraftResource;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Wizard draft CRUD + autosave. Read = marketing.view; write = marketing.ads.create.
 * Marketing is intentionally Owner/Admin-only (they hold the `*` wildcard; no staff
 * role lists any `marketing.*` permission) — same policy as the other Marketing
 * controllers. All lookups are tenant-scoped via the model global scope (foreign
 * ids ⇒ 404), so cross-tenant read/update/delete is impossible.
 */
class AdDraftController extends Controller
{
    public function __construct(private AdDraftService $service) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('marketing.view');

        return AdDraftResource::collection(AdDraft::query()->latest('id')->get());
    }

    public function store(AdDraftRequest $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $account = AdAccount::query()->findOrFail((int) $request->integer('ad_account_id'));

        $draft = $this->service->create($account->id, $request->user()?->id, $request->validated());

        return (new AdDraftResource($draft))->response()->setStatusCode(201);
    }

    public function show(int $id): AdDraftResource
    {
        Gate::authorize('marketing.view');

        return new AdDraftResource(AdDraft::query()->findOrFail($id));
    }

    public function update(int $id, AdDraftRequest $request): AdDraftResource
    {
        Gate::authorize('marketing.ads.create');
        $draft = AdDraft::query()->findOrFail($id);

        return new AdDraftResource($this->service->update($draft, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $draft = AdDraft::query()->findOrFail($id);
        // Don't delete a draft mid-publish — the PublishAdDraft job (Plan 4) still
        // holds its id. A `failed` draft IS deletable (that's the rollback trigger).
        abort_if($draft->status === AdDraft::STATUS_PUBLISHING, 422, 'Đang xuất bản — không thể xoá bản nháp lúc này.');
        $draft->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
