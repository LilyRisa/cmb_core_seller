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

    /** Clone a draft into a new editable draft (fresh, publishes new entities). */
    public function duplicate(AdDraft $draft, ?int $userId): AdDraft
    {
        return AdDraft::create([
            'ad_account_id' => $draft->ad_account_id,
            'created_by' => $userId,
            'name' => trim(($draft->name ?? 'Bản nháp').' (sao chép)'),
            'objective' => $draft->objective,
            'payload' => $this->resetExternalIds((array) ($draft->payload ?? [])),
            'status' => AdDraft::STATUS_DRAFT,
        ]);
    }

    /**
     * Null out external_id on every adset/ad so the copy publishes as new objects.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function resetExternalIds(array $payload): array
    {
        if (isset($payload['adsets']) && is_array($payload['adsets'])) {
            $payload['adsets'] = array_map(function ($as) {
                if (! is_array($as)) {
                    return $as;
                }
                $as['external_id'] = null;
                if (isset($as['ads']) && is_array($as['ads'])) {
                    $as['ads'] = array_map(function ($ad) {
                        if (is_array($ad)) {
                            $ad['external_id'] = null;
                        }

                        return $ad;
                    }, $as['ads']);
                }

                return $as;
            }, $payload['adsets']);
        }

        return $payload;
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
