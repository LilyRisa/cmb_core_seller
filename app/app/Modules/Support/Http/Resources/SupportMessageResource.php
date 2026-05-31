<?php

namespace CMBcoreSeller\Modules\Support\Http\Resources;

use CMBcoreSeller\Modules\Support\Models\SupportMessage;
use CMBcoreSeller\Modules\Support\Models\SupportMessageAttachment;
use CMBcoreSeller\Modules\Support\Services\SupportMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 1 tin nhắn CSKH + đính kèm (download_url ký TTL ngắn). Dùng chung cho cả phía
 * user lẫn admin.
 *
 * @mixin SupportMessage
 */
class SupportMessageResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        $storage = app(SupportMediaStorage::class);

        return [
            'id' => $this->id,
            'sender' => $this->sender,       // user|cskh
            'type' => $this->type,           // text|system
            'body' => $this->body,
            'attachments_count' => (int) $this->attachments_count,
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn (SupportMessageAttachment $a) => [
                'id' => $a->id,
                'kind' => $a->kind,          // image|video|file
                'mime' => $a->mime,
                'size_bytes' => $a->size_bytes,
                'filename' => $a->filename,
                'status' => $a->status,
                'download_url' => $storage->temporaryUrl($a),
            ])->values()->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
