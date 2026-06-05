<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookMoney;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Live edits to one entity (campaign/adset/ad): rename, daily budget, pause/resume.
 * Writes to the provider then mirrors the change onto the local AdEntity row so the
 * report reflects it immediately. Write permission marketing.ads.create.
 */
class AdEntityController extends Controller
{
    /** PATCH ad-accounts/{id}/entities/{externalId} { level, name?, daily_budget_major?, status? } */
    public function update(int $id, string $externalId, Request $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');

        $validated = $request->validate([
            'level' => ['required', Rule::in(['campaign', 'adset', 'ad'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'daily_budget_major' => ['sometimes', 'integer', 'min:1000'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'PAUSED'])],
        ]);

        /** @var AdAccount $account */
        $account = AdAccount::query()->findOrFail($id);
        $registry = app(AdsRegistry::class);
        $connector = $registry->has($account->provider) ? $registry->for($account->provider) : null;
        abort_unless($connector instanceof AdsWriteConnector, 422, 'Tính năng chỉnh sửa chưa được bật cho nhà cung cấp này.');
        abort_unless($connector->supports('actions.budget') || $connector->supports('actions.status'), 422, 'Nhà cung cấp không hỗ trợ chỉnh sửa.');
        $account->assertAutomationOwner();

        $fields = array_intersect_key($validated, array_flip(['name', 'daily_budget_major', 'status']));
        abort_if($fields === [], 422, 'Không có trường nào để cập nhật.');

        $connector->updateEntity((string) $account->access_token, (string) $validated['level'], $externalId, $fields, (string) ($account->currency ?? 'VND'));

        // Mirror onto the local entity row so the report updates without a full re-sync.
        $entity = AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('external_id', $externalId)
            ->first();
        if ($entity !== null) {
            if (isset($fields['name'])) {
                $entity->name = (string) $fields['name'];
            }
            if (isset($fields['daily_budget_major'])) {
                $entity->daily_budget = (int) FacebookMoney::toMinorUnits((int) $fields['daily_budget_major'], (string) ($account->currency ?? 'VND'));
            }
            if (isset($fields['status'])) {
                $entity->status = (string) $fields['status'];
                $entity->effective_status = (string) $fields['status'];
            }
            $entity->save();
        }

        return response()->json(['data' => ['updated' => true]]);
    }
}
