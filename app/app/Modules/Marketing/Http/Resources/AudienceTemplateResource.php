<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Resources;

use CMBcoreSeller\Modules\Marketing\Models\AudienceTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AudienceTemplate */
class AudienceTemplateResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        $payload = (array) ($this->payload ?? []);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'payload' => [
                'include' => $payload['include'] ?? [],
                'narrow' => $payload['narrow'] ?? [],
                'exclude' => $payload['exclude'] ?? [],
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
