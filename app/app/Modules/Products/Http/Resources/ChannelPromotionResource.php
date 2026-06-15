<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Resources;

use CMBcoreSeller\Modules\Products\Models\ChannelPromotion;
use CMBcoreSeller\Modules\Products\Models\ChannelPromotionSku;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChannelPromotion */
class ChannelPromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_account_id' => $this->channel_account_id,
            'provider' => $this->provider,
            'external_promotion_id' => $this->external_promotion_id,
            'title' => $this->title,
            'discount_type' => $this->discount_type,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status,
            'source' => $this->source,
            'last_error' => $this->last_error,
            'pushed_at' => $this->pushed_at?->toIso8601String(),
            'synced_at' => $this->synced_at?->toIso8601String(),
            'sku_count' => $this->whenCounted('skus'),
            'skus' => $this->whenLoaded('skus', fn () => $this->skus->map(fn (ChannelPromotionSku $s) => [
                'id' => $s->id,
                'channel_listing_id' => $s->channel_listing_id,
                'external_product_id' => $s->external_product_id,
                'external_sku_id' => $s->external_sku_id,
                'seller_sku' => $s->seller_sku,
                'base_price' => (int) $s->base_price,
                'discount_value' => (int) $s->discount_value,
                'sale_price' => (int) $s->sale_price,
                'push_status' => $s->push_status,
                'error' => $s->error,
            ])->all()),
        ];
    }
}
