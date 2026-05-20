<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Resources;

use CMBcoreSeller\Modules\Messaging\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'external_message_id' => $this->external_message_id,
            'direction' => $this->direction,
            'kind' => $this->kind,
            'body' => $this->body,
            'attachments_count' => (int) $this->attachments_count,
            'sent_by_user_id' => $this->sent_by_user_id,
            'sent_by_ai' => (bool) $this->sent_by_ai,
            'delivery_status' => $this->delivery_status,
            'failure_code' => $this->failure_code,
            'reply_to_message_id' => $this->reply_to_message_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'kind' => $a->kind,
                    'mime' => $a->mime,
                    'size_bytes' => $a->size_bytes,
                    'width' => $a->width,
                    'height' => $a->height,
                    'duration_ms' => $a->duration_ms,
                    'filename' => $a->filename,
                    'status' => $a->status,
                    // KHÔNG lộ storage_path raw — phải qua signed URL.
                    // S2 sẽ thêm `download_url` qua `MediaController::signed()`.
                ]);
            }),
        ];
    }
}
