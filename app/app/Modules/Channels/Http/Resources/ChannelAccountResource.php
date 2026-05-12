<?php

namespace CMBcoreSeller\Modules\Channels\Http\Resources;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChannelAccount
 */
class ChannelAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'external_shop_id' => $this->external_shop_id,
            'shop_name' => $this->shop_name,
            'display_name' => $this->display_name,
            'name' => $this->effectiveName(),
            'shop_region' => $this->shop_region,
            'seller_type' => $this->seller_type,
            'status' => $this->status,
            'token_expires_at' => $this->token_expires_at?->toIso8601String(),
            'refresh_token_expires_at' => $this->refresh_token_expires_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'last_webhook_at' => $this->last_webhook_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            // never expose tokens; expose only that a shop_cipher exists
            'has_shop_cipher' => ! empty($this->meta['shop_cipher'] ?? null),
        ];
    }
}
