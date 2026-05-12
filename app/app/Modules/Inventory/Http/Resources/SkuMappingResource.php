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
            'channel_listing' => $this->whenLoaded('channelListing', fn () => $this->channelListing ? [
                'id' => $this->channelListing->id,
                'channel_account_id' => $this->channelListing->channel_account_id,
                'external_sku_id' => $this->channelListing->external_sku_id,
                'seller_sku' => $this->channelListing->seller_sku,
                'title' => $this->channelListing->title,
            ] : null),
        ];
    }
}
