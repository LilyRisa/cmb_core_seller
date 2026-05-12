<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Resources;

use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryLevel */
class InventoryLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku_id' => $this->sku_id,
            'warehouse_id' => $this->warehouse_id,
            'on_hand' => $this->on_hand,
            'reserved' => $this->reserved,
            'safety_stock' => $this->safety_stock,
            'available' => $this->available_cached,
            'is_negative' => $this->is_negative,
            'sku' => $this->whenLoaded('sku', fn () => ['id' => $this->sku?->id, 'sku_code' => $this->sku?->sku_code, 'name' => $this->sku?->name]),
            'warehouse' => $this->whenLoaded('warehouse', fn () => ['id' => $this->warehouse?->id, 'name' => $this->warehouse?->name, 'is_default' => $this->warehouse?->is_default]),
        ];
    }
}
