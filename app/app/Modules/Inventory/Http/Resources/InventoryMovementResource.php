<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Resources;

use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryMovement */
class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku_id' => $this->sku_id,
            'warehouse_id' => $this->warehouse_id,
            'qty_change' => $this->qty_change,
            'type' => $this->type,
            'ref_type' => $this->ref_type,
            'ref_id' => $this->ref_id,
            'balance_after' => $this->balance_after,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
