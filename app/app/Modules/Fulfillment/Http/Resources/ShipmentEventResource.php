<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use CMBcoreSeller\Modules\Fulfillment\Models\ShipmentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShipmentEvent */
class ShipmentEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'source' => $this->source,
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
