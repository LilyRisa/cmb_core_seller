<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Resources;

use CMBcoreSeller\Modules\Messaging\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MessageTemplate
 */
class MessageTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'body' => $this->body,
            'vars' => $this->vars ?? [],
            'attachments' => $this->attachments ?? [],
            'scope' => $this->scope ?? [],
            'shortcut_key' => $this->shortcut_key,
            'enabled' => (bool) $this->enabled,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
