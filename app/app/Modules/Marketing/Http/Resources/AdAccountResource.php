<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Resources;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Support\AccountHealth;
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
            'business_picture_url' => $this->business_picture_url,
            'external_account_id' => $this->external_account_id,
            'name' => $this->name,
            'currency' => $this->currency,
            'status' => $this->status,
            'fb_account_status' => $this->fb_account_status,
            'disable_reason' => $this->disable_reason,
            'health' => AccountHealth::describe($this->fb_account_status, $this->disable_reason),
            'health_checked_at' => $this->health_checked_at?->toIso8601String(),
            // Multi-tenant connection: only the automation owner may run monitors / edits.
            'shared_with_other_tenants' => $this->sharedWithOtherTenants(),
            'is_automation_owner' => $this->isAutomationOwner(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'insights_synced_at' => $this->insights_synced_at?->toIso8601String(),
            // NEVER expose access_token / refresh_token.
        ];
    }
}
