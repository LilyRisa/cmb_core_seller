<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Resources\AdAccountResource;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdAccountEntities;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdInsights;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Ad accounts API (tenant-scoped via global scope). Tokens are never exposed.
 */
class AdAccountController extends Controller
{
    /** GET /api/v1/marketing/ad-accounts */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('marketing.view');

        return AdAccountResource::collection(
            AdAccount::query()->latest('id')->get(),
        );
    }

    /** DELETE /api/v1/marketing/ad-accounts/{id} — soft-delete (disconnect). */
    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('marketing.connect');
        AdAccount::query()->findOrFail($id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * POST /api/v1/marketing/ad-accounts/disconnect-bulk
     * Disconnect many accounts at once — by ids[] and/or a whole business_id.
     */
    public function disconnectBulk(Request $request): JsonResponse
    {
        Gate::authorize('marketing.connect');
        $validated = $request->validate([
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['integer'],
            'business_id' => ['sometimes', 'nullable', 'string'],
        ]);

        $query = AdAccount::query();
        $hasFilter = false;
        if (! empty($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
            $hasFilter = true;
        }
        if (! empty($validated['business_id'])) {
            // Whole BM: tenant-scoped accounts under this business.
            $bizQuery = AdAccount::query()->where('business_id', $validated['business_id']);
            if ($hasFilter) {
                $query->orWhere(fn ($q) => $q->where('business_id', $validated['business_id']));
            } else {
                $query = $bizQuery;
                $hasFilter = true;
            }
        }
        abort_unless($hasFilter, 422, 'Hãy chọn tài khoản hoặc BM để ngắt.');

        $count = 0;
        foreach ($query->get() as $account) {
            $account->delete();
            $count++;
        }

        return response()->json(['data' => ['deleted' => $count]]);
    }

    /** POST /api/v1/marketing/ad-accounts/{id}/refresh — kick a near-real-time insights sync now. */
    public function refresh(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);
        // Re-sync cả cây entity (campaign/adset/ad) lẫn insights — để "Làm mới" repopulate
        // campaign nếu lần sync lúc connect chưa chạy (vd queue marketing-sync chưa có worker).
        SyncAdAccountEntities::dispatch((int) $account->getKey());
        SyncAdInsights::dispatch((int) $account->getKey());

        return response()->json(['data' => ['queued' => true, 'ad_account_id' => (int) $account->getKey()]]);
    }
}
