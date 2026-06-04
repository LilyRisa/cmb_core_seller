<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Resources;

use CMBcoreSeller\Modules\Marketing\Models\GeoExclusionTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GeoExclusionTemplate */
class GeoExclusionTemplateResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'payload' => $this->payload ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
