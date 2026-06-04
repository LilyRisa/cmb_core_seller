<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Resources;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdAccount
 */
class AdAccountResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'business_id' => $this->business_id,
            'business_name' => $this->business_name,
            'external_account_id' => $this->external_account_id,
            'name' => $this->name,
            'currency' => $this->currency,
            'status' => $this->status,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'insights_synced_at' => $this->insights_synced_at?->toIso8601String(),
            // NEVER expose access_token / refresh_token.
        ];
    }
}
