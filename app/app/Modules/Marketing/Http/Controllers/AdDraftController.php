<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Modules\Marketing\Http\Requests\AdDraftRequest;
use CMBcoreSeller\Modules\Marketing\Http\Resources\AdDraftResource;
use CMBcoreSeller\Modules\Marketing\Jobs\PublishAdDraft;
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

        // Bản nháp thuộc về một tài khoản quảng cáo — lọc theo ad_account_id để mỗi
        // tài khoản chỉ thấy nháp của chính nó (đổi tài khoản ⇒ danh sách nháp đổi theo).
        $accountId = (int) request()->integer('ad_account_id');

        return AdDraftResource::collection(
            AdDraft::query()
                ->when($accountId > 0, fn ($q) => $q->where('ad_account_id', $accountId))
                ->latest('id')
                ->get(),
        );
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

    /** POST ad-drafts/{id}/duplicate — clone into a new editable draft. */
    public function duplicate(int $id): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $draft = AdDraft::query()->findOrFail($id);
        $copy = $this->service->duplicate($draft, request()->user()?->id);

        return (new AdDraftResource($copy))->response()->setStatusCode(201);
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

    /** POST ad-drafts/{id}/publish — enqueue create-on-Facebook (gated by ads.create capability). */
    public function publish(int $id, AdsRegistry $registry): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $draft = AdDraft::query()->findOrFail($id);
        $account = AdAccount::query()->findOrFail($draft->ad_account_id);
        $account->assertAutomationOwner();

        $connector = $registry->has($account->provider) ? $registry->for($account->provider) : null;
        abort_unless(
            $connector instanceof AdsWriteConnector && $connector->supports('ads.create'),
            422,
            'Tạo quảng cáo chưa được bật cho tài khoản này (cần quyền ads_management + Standard Access).',
        );

        PublishAdDraft::dispatch($draft->id);
        $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHING, 'last_error' => null])->save();

        return response()->json(['data' => ['queued' => true, 'status' => 'publishing']]);
    }
}
