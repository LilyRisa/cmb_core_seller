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
            'spu_code' => $this->spu_code,
            'category' => $this->category,
            'sku_code' => $this->sku_code,
            'barcode' => $this->barcode,
            'gtins' => $this->gtins ?? [],
            'name' => $this->name,
            'base_unit' => $this->base_unit,
            'cost_price' => $this->cost_price,
            'cost_method' => $this->cost_method,
            'last_receipt_cost' => $this->last_receipt_cost,
            'effective_cost' => $this->effectiveCost(),
            'ref_sale_price' => $this->ref_sale_price,
            'ref_profit_per_unit' => $this->refProfitPerUnit(),
            'ref_margin_percent' => $this->refMarginPercent(),
            'sale_start_date' => $this->sale_start_date?->toDateString(),
            'note' => $this->note,
            'weight_grams' => $this->weight_grams,
            'length_cm' => $this->length_cm,
            'width_cm' => $this->width_cm,
            'height_cm' => $this->height_cm,
            'image_url' => $this->image_url,
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
