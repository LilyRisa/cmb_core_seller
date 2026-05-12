<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Resources;

use CMBcoreSeller\Modules\Inventory\Models\Sku;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sku
 */
class SkuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku_code' => $this->sku_code,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'cost_price' => $this->cost_price,
            'attributes' => $this->attributes ?? [],
            'is_active' => $this->is_active,
            'on_hand_total' => $this->whenLoaded('levels', fn () => (int) $this->levels->sum('on_hand')),
            'reserved_total' => $this->whenLoaded('levels', fn () => (int) $this->levels->sum('reserved')),
            'available_total' => $this->whenLoaded('levels', fn () => (int) $this->levels->sum('available_cached')),
            'levels' => InventoryLevelResource::collection($this->whenLoaded('levels')),
            'mappings' => SkuMappingResource::collection($this->whenLoaded('mappings')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
