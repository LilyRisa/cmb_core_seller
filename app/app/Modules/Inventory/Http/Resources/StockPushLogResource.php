<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Resources;

use CMBcoreSeller\Modules\Inventory\Models\StockPushLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockPushLog */
class StockPushLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'desired_qty' => $this->desired_qty,
            'seller_sku' => $this->seller_sku,
            'external_sku_id' => $this->external_sku_id,
            'error' => $this->error,
            'channel_account_id' => $this->channel_account_id,
            'channel_listing_id' => $this->channel_listing_id,
            'shop_name' => $this->whenLoaded('channelAccount', fn () => $this->channelAccount?->shop_name),
            'provider' => $this->whenLoaded('channelAccount', fn () => $this->channelAccount?->provider),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
