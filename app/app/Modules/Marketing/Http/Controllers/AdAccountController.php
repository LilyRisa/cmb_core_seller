<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Http\Resources\AdAccountResource;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdAccountEntities;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdInsights;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Services\AdsReportService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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

    /** POST /api/v1/marketing/ad-accounts/{id}/claim-automation — take over automation/write ownership. */
    public function claimAutomation(int $id): JsonResponse
    {
        Gate::authorize('marketing.connect');
        $account = AdAccount::query()->findOrFail($id);
        $account->claimAutomation();

        return response()->json(['data' => ['is_automation_owner' => true]]);
    }

    /**
     * POST /api/v1/marketing/ad-accounts/refresh-accounts
     * Re-list ad accounts from Facebook using the stored tokens → update name /
     * currency / BM / health and DISCOVER newly-added accounts (no re-OAuth needed).
     */
    public function refreshAccounts(AdsRegistry $registry): JsonResponse
    {
        Gate::authorize('marketing.view');

        $accounts = AdAccount::query()->get();
        if ($accounts->isEmpty()) {
            return response()->json(['data' => ['updated' => 0, 'created' => 0]]);
        }

        $created = 0;
        $updated = 0;
        $seenTokens = [];
        foreach ($accounts as $acc) {
            $token = (string) $acc->access_token;
            $provider = $acc->provider;
            if ($token === '' || isset($seenTokens[$provider.'|'.$token]) || ! $registry->has($provider)) {
                continue;
            }
            $seenTokens[$provider.'|'.$token] = true;

            try {
                $dtos = $registry->for($provider)->listAdAccounts($token);
            } catch (\Throwable $e) {
                Log::warning('marketing.refresh_accounts.failed', ['account' => $acc->getKey(), 'error' => $e->getMessage()]);

                continue;
            }

            foreach ($dtos as $dto) {
                /** @var AdAccount $row */
                $row = AdAccount::withoutGlobalScope(TenantScope::class)->firstOrNew([
                    'tenant_id' => $acc->tenant_id,
                    'provider' => $provider,
                    'external_account_id' => $dto->externalAccountId,
                ]);
                $isNew = ! $row->exists;
                $fill = [
                    'tenant_id' => $acc->tenant_id,
                    'name' => $dto->name,
                    'currency' => $dto->currency,
                    'business_id' => $dto->businessId,
                    'business_name' => $dto->businessName,
                    'business_picture_url' => $dto->businessPictureUrl,
                    'fb_account_status' => $dto->accountStatus,
                    'disable_reason' => $dto->disableReason,
                    'health_checked_at' => now(),
                    'deleted_at' => null, // restore if it was disconnected
                ];
                if ($isNew) {
                    $fill['access_token'] = $token;       // new accounts inherit this token
                    $fill['status'] = AdAccount::STATUS_ACTIVE;
                    $fill['created_by'] = $acc->created_by;
                }
                $row->forceFill($fill)->save();
                $isNew ? $created++ : $updated++;
                if ($isNew) {
                    SyncAdAccountEntities::dispatch((int) $row->getKey());
                }
            }
        }

        return response()->json(['data' => ['updated' => $updated, 'created' => $created]]);
    }

    /** POST /api/v1/marketing/ad-accounts/{id}/refresh — kick a near-real-time insights sync now. */
    public function refresh(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);
        // Bust the on-demand report cache so the next /report returns fresh Graph data
        // immediately (the table refetches client-side), then re-sync the entity tree +
        // snapshots in the background (picks up new campaigns / latest metrics).
        AdsReportService::bumpCache((int) $account->getKey());
        SyncAdAccountEntities::dispatch((int) $account->getKey());
        SyncAdInsights::dispatch((int) $account->getKey());

        return response()->json(['data' => ['queued' => true, 'ad_account_id' => (int) $account->getKey()]]);
    }
}
