<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingLabelTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'paper' => $this->paper,
            'paper_w_mm' => (int) $this->paper_w_mm,
            'paper_h_mm' => (int) $this->paper_h_mm,
            'schema_version' => (int) $this->schema_version,
            'schema' => $this->schema,
            'is_default' => (bool) $this->is_default,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
