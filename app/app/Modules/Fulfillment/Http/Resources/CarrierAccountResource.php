<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CarrierAccount — never exposes the raw `credentials` (just which keys are set). */
class CarrierAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'carrier' => $this->carrier,
            'name' => $this->name,
            'default_service' => $this->default_service,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'meta' => $this->meta ?? [],
            'credential_keys' => array_keys((array) ($this->credentials ?? [])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
