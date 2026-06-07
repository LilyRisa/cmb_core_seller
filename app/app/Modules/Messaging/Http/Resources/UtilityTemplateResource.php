<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Resources;

use CMBcoreSeller\Modules\Messaging\Models\UtilityTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UtilityTemplate
 */
class UtilityTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_account_id' => $this->channel_account_id,
            'code' => $this->code,
            'name' => $this->name,
            'language' => $this->language,
            'body' => $this->body,
            'buttons' => $this->buttons ?? [],
            'variables' => $this->variables ?? [],
            'external_template_id' => $this->external_template_id,
            'status' => $this->status,
            'reject_reason' => $this->reject_reason,
            'enabled' => (bool) $this->enabled,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
