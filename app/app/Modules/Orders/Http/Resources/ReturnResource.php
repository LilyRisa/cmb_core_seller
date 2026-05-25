<?php

namespace CMBcoreSeller\Modules\Orders\Http\Resources;

use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderReturn
 */
class ReturnResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'kind' => $this->kind,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'raw_status' => $this->raw_status,
            'external_return_id' => $this->external_return_id,
            'external_order_id' => $this->external_order_id,
            'order_id' => $this->order_id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order?->order_number),
            'reason' => $this->reason,
            'refund_amount' => (int) $this->refund_amount,
            'currency' => $this->currency,
            'items' => $this->items ?? [],
            'requested_at' => $this->requested_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
