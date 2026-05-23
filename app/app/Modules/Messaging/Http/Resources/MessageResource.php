<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Resources;

use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
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
            'reaction' => $this->meta['reaction'] ?? null,
            // Nút bấm (template/quick-reply của trả lời tự động Facebook) — chỉ hiển thị.
            'buttons' => array_values(array_filter(
                (array) ($this->meta['buttons'] ?? []),
                fn ($b) => is_array($b) && ($b['title'] ?? '') !== '',
            )),
            'attachments' => $this->whenLoaded('attachments', function () {
                $storage = app(MediaStorage::class);

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
                    // KHÔNG lộ storage_path raw — chỉ signed URL TTL ngắn (§8.5).
                    // Fallback `external_url` (URL CDN sàn) khi relay chưa xong / thất bại
                    // ⇒ FE vẫn render được ảnh/video/sticker thay vì rơi xuống link "Tệp đính kèm".
                    'download_url' => $storage->temporaryUrl($a) ?? $a->external_url,
                ]);
            }),
        ];
    }
}
