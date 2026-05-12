<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Resources;

use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SkuMapping */
class SkuMappingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_listing_id' => $this->channel_listing_id,
            'sku_id' => $this->sku_id,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'sku' => $this->whenLoaded('sku', fn () => ['id' => $this->sku?->id, 'sku_code' => $this->sku?->sku_code, 'name' => $this->sku?->name]),
        ];
    }
}
