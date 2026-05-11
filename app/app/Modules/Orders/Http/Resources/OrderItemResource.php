<?php

namespace CMBcoreSeller\Modules\Orders\Http\Resources;

use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_item_id' => $this->external_item_id,
            'external_product_id' => $this->external_product_id,
            'external_sku_id' => $this->external_sku_id,
            'seller_sku' => $this->seller_sku,
            'sku_id' => $this->sku_id,           // null until SKU mapping (Phase 2)
            'is_mapped' => $this->sku_id !== null,
            'name' => $this->name,
            'variation' => $this->variation,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'subtotal' => $this->subtotal,
            'image' => $this->image,
            'currency' => 'VND',
        ];
    }
}
