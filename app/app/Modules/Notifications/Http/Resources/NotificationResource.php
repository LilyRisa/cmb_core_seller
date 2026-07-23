<?php

namespace CMBcoreSeller\Modules\Notifications\Http\Resources;

use CMBcoreSeller\Modules\Notifications\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 1 thông báo in-app cho chuông/danh sách (SPEC 0036). `is_read` suy từ `read_at` cho FE
 * tiện render; `data` mang id thực thể để FE bổ sung ngữ cảnh nếu cần.
 *
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'level' => $this->level,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->action_url,
            'data' => $this->data,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
