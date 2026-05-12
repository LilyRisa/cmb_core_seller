<?php

namespace CMBcoreSeller\Modules\Products\Http\Resources;

use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChannelListing */
class ChannelListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_account_id' => $this->channel_account_id,
            'external_product_id' => $this->external_product_id,
            'external_sku_id' => $this->external_sku_id,
            'seller_sku' => $this->seller_sku,
            'title' => $this->title,
            'variation' => $this->variation,
            'price' => $this->price,
            'channel_stock' => $this->channel_stock,
            'currency' => $this->currency,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'is_stock_locked' => $this->is_stock_locked,
            'sync_status' => $this->sync_status,
            'sync_error' => $this->sync_error,
            'last_pushed_at' => $this->last_pushed_at?->toIso8601String(),
            'is_mapped' => $this->relationLoaded('mappings') ? $this->mappings->isNotEmpty() : null,
            'mappings' => $this->whenLoaded('mappings', fn () => $this->mappings->map(fn ($m) => [
                'id' => $m->id, 'sku_id' => $m->sku_id, 'quantity' => $m->quantity, 'type' => $m->type,
                'sku' => $m->sku ? ['id' => $m->sku->id, 'sku_code' => $m->sku->sku_code, 'name' => $m->sku->name] : null,
            ])->values()->all()),
        ];
    }
}
