<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Resources;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdDraft */
class AdDraftResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ad_account_id' => $this->ad_account_id,
            'name' => $this->name,
            'status' => $this->status,
            'objective' => $this->objective,
            'payload' => $this->payload ?? [],
            'campaign_external_id' => $this->campaign_external_id,
            'adset_external_id' => $this->adset_external_id,
            'ad_external_id' => $this->ad_external_id,
            'last_error' => $this->last_error,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
