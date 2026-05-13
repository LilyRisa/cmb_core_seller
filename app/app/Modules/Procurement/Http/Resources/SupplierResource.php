<?php

namespace CMBcoreSeller\Modules\Procurement\Http\Resources;

use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Supplier */
class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'payment_terms_days' => (int) $this->payment_terms_days,
            'note' => $this->note,
            'is_active' => (bool) $this->is_active,
            'prices_count' => $this->whenLoaded('prices', fn () => $this->prices->count()),
            'prices' => $this->whenLoaded('prices', fn () => $this->prices->map(fn ($p) => [
                'id' => $p->id, 'sku_id' => $p->sku_id, 'unit_cost' => (int) $p->unit_cost, 'moq' => (int) $p->moq,
                'currency' => $p->currency, 'is_default' => (bool) $p->is_default,
                'valid_from' => $p->valid_from?->format('Y-m-d'), 'valid_to' => $p->valid_to?->format('Y-m-d'),
                'sku' => $p->relationLoaded('sku') && $p->sku ? ['id' => $p->sku->id, 'sku_code' => $p->sku->sku_code, 'name' => $p->sku->name, 'image_url' => $p->sku->image_url] : null,
            ])->values()->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
