<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Idempotent upsert of ad entities. Parents (campaign) are synced before children
 * (adset, ad) so `parent_id` resolves from the already-stored parent row.
 */
class AdsSyncService
{
    public function upsertEntity(AdAccount $account, AdEntityDTO $dto): AdEntity
    {
        $parentId = null;
        if ($dto->parentExternalId !== null && $dto->parentExternalId !== '') {
            $parentId = AdEntity::withoutGlobalScope(TenantScope::class)
                ->where('ad_account_id', $account->getKey())
                ->where('external_id', $dto->parentExternalId)
                ->value('id');
        }

        /** @var AdEntity $entity */
        $entity = AdEntity::withoutGlobalScope(TenantScope::class)->firstOrNew([
            'ad_account_id' => (int) $account->getKey(),
            'level' => $dto->level,
            'external_id' => $dto->externalId,
        ]);
        if (! $entity->exists) {
            $entity->tenant_id = (int) $account->tenant_id;
        }
        $entity->parent_external_id = $dto->parentExternalId;
        $entity->parent_id = $parentId !== null ? (int) $parentId : null;
        $entity->name = $dto->name;
        $entity->status = $dto->status;
        $entity->effective_status = $dto->effectiveStatus;
        $entity->daily_budget = $dto->dailyBudget;
        $entity->lifetime_budget = $dto->lifetimeBudget;
        $entity->save();

        return $entity;
    }
}
