<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;

/**
 * CRUD for wizard drafts. tenant_id is auto-set by BelongsToTenant. Update only
 * overwrites the fields present in the request (autosave sends partial payloads).
 */
class AdDraftService
{
    /** @param array<string,mixed> $data */
    public function create(int $adAccountId, ?int $userId, array $data): AdDraft
    {
        return AdDraft::create([
            'ad_account_id' => $adAccountId,
            'created_by' => $userId,
            'name' => $data['name'] ?? null,
            'objective' => $data['objective'] ?? null,
            'payload' => $data['payload'] ?? [],
            'status' => AdDraft::STATUS_DRAFT,
        ]);
    }

    /** @param array<string,mixed> $data */
    public function update(AdDraft $draft, array $data): AdDraft
    {
        foreach (['name', 'objective', 'payload'] as $field) {
            if (array_key_exists($field, $data)) {
                $draft->{$field} = $data[$field];
            }
        }
        $draft->save();

        return $draft;
    }
}
