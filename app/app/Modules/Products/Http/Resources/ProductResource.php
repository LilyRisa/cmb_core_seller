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
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
