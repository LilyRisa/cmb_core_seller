<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Resources;

use CMBcoreSeller\Modules\Messaging\Models\MessageTemplate;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MessageTemplate
 */
class MessageTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(MediaStorage::class);
        $attachments = [];
        foreach ((array) ($this->attachments ?? []) as $att) {
            if (! is_array($att) || empty($att['storage_path'])) {
                continue;
            }
            $attachments[] = [
                'storage_path' => (string) $att['storage_path'],
                'kind' => (string) ($att['kind'] ?? 'image'),
                'mime' => $att['mime'] ?? null,
                'url' => $storage->temporaryUrlForPath((string) $att['storage_path']),
            ];
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'body' => $this->body,
            'vars' => $this->vars ?? [],
            'attachments' => $attachments,
            'scope' => $this->scope ?? [],
            'shortcut_key' => $this->shortcut_key,
            'enabled' => (bool) $this->enabled,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
