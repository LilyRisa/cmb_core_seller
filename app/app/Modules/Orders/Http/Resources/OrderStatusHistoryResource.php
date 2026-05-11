<?php

namespace CMBcoreSeller\Modules\Orders\Http\Resources;

use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderStatusHistory
 */
class OrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'to_status_label' => StandardOrderStatus::tryFrom((string) $this->to_status)?->label() ?? $this->to_status,
            'raw_status' => $this->raw_status,
            'source' => $this->source,
            'changed_at' => $this->changed_at?->toIso8601String(),
        ];
    }
}
