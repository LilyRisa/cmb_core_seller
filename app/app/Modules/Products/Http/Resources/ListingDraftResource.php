<?php

namespace CMBcoreSeller\Modules\Products\Http\Resources;

use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\ListingDraftSku;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ListingDraft */
class ListingDraftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'channel_account_id' => $this->channel_account_id,
            'provider' => $this->provider,
            'status' => $this->status,
            'name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'description' => $this->attributes['description'] ?? null,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'attributes' => $this->attributes ?? [],
            'media_refs' => $this->media_refs ?? [],
            'logistics' => $this->logistics ?? [],
            'validation_errors' => $this->validation_errors ?? [],
            'external_item_id' => $this->external_item_id,
            'raw_qc_status' => $this->raw_qc_status,
            'skus' => $this->whenLoaded('skus', fn () => $this->skus->map(fn (ListingDraftSku $s) => [
                'id' => $s->id,
                'seller_sku' => $s->seller_sku,
                'sale_props' => $s->sale_props ?? [],
                'price' => $s->price,
                'stock' => $s->stock,
                'package_weight' => $s->package_weight,
                'package_dims' => $s->package_dims ?? [],
                'warehouse_id' => $this->logistics['warehouse_id'] ?? null,
                'master_variant_id' => $s->master_variant_id,
                'master_sku' => $s->relationLoaded('masterSku') && $s->masterSku
                    ? ['id' => $s->masterSku->id, 'sku_code' => $s->masterSku->sku_code, 'name' => $s->masterSku->name]
                    : null,
            ])->values()->all()),
        ];
    }
}
