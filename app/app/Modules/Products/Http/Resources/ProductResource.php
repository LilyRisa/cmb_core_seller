<?php

namespace CMBcoreSeller\Modules\Products\Http\Resources;

use CMBcoreSeller\Modules\Products\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'brand' => $this->brand,
            'category' => $this->category,
            'meta' => $this->meta ?? [],
            'skus_count' => $this->whenCounted('skus'),
            'listings' => $this->whenLoaded('listingDrafts', fn () => $this->listingDrafts->map(fn ($l) => [
                'id' => $l->id,
                'provider' => $l->provider,
                'channel_account_id' => $l->channel_account_id,
                'status' => $l->status,
                'external_item_id' => $l->external_item_id,
                'raw_qc_status' => $l->raw_qc_status,
            ])->values()->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
