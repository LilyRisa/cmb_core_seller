<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Shipment */
class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'carrier' => $this->carrier,
            'carrier_account_id' => $this->carrier_account_id,
            'tracking_no' => $this->tracking_no,
            'package_no' => $this->package_no,
            'status' => $this->status,
            'service' => $this->service,
            'weight_grams' => $this->weight_grams,
            'cod_amount' => $this->cod_amount,
            'fee' => $this->fee,
            'label_url' => $this->label_url,
            'has_label' => filled($this->label_path),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'order' => $this->whenLoaded('order', fn () => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'external_order_id' => $this->order->external_order_id,
                'status' => $this->order->status->value,
                'source' => $this->order->source,
                'buyer_name' => $this->order->buyer_name,
                'grand_total' => $this->order->grand_total,
            ] : null),
            'events' => ShipmentEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
