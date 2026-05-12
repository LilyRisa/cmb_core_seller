<?php

namespace CMBcoreSeller\Modules\Channels\Http\Resources;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Safe view of a stored inbound webhook. The raw `payload` may carry buyer PII
 * (SPEC 0001 §8) so it is **not** exposed here — only routing/processing metadata.
 *
 * @mixin WebhookEvent
 */
class WebhookEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ChannelAccount|null $account */
        $account = $this->relationLoaded('channelAccount') ? $this->getRelation('channelAccount') : null;

        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'event_type' => $this->event_type,
            'raw_type' => $this->raw_type,
            'external_id' => $this->external_id,
            'external_shop_id' => $this->external_shop_id,
            'channel_account_id' => $this->channel_account_id,
            'shop_name' => $account ? ($account->shop_name ?? $account->external_shop_id) : null,
            'signature_ok' => (bool) $this->signature_ok,
            'status' => $this->status,
            'attempts' => (int) ($this->attempts ?? 0),
            'error' => $this->error,
            'received_at' => $this->received_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
        ];
    }
}
