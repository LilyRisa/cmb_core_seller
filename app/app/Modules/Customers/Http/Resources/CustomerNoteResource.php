<?php

namespace CMBcoreSeller\Modules\Customers\Http\Resources;

use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CustomerNote
 */
class CustomerNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'author_user_id' => $this->author_user_id,
            'is_auto' => $this->isAuto(),
            'kind' => $this->kind,
            'severity' => $this->severity,
            'note' => $this->note,
            'order_id' => $this->order_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
