<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Resources;

use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Warehouse */
class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'is_default' => $this->is_default,
        ];
    }
}
