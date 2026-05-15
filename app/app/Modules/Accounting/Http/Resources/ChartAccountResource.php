<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChartAccount */
class ChartAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'normal_balance' => $this->normal_balance,
            'parent_id' => $this->parent_id,
            'parent_code' => $this->whenLoaded('parent', fn () => $this->parent?->code),
            'is_postable' => (bool) $this->is_postable,
            'is_active' => (bool) $this->is_active,
            'vas_template' => $this->vas_template,
            'sort_order' => (int) $this->sort_order,
            'description' => $this->description,
        ];
    }
}
