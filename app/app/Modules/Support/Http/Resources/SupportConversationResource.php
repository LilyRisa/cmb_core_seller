<?php

namespace CMBcoreSeller\Modules\Support\Http\Resources;

use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 1 đoạn hội thoại CSKH + danh sách tin (khi đã eager-load `messages.attachments`).
 * Dùng cho phía user (`GET /support/conversations`) và thread admin.
 *
 * @mixin SupportConversation
 */
class SupportConversationResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,            // open|closed
            'last_sender' => $this->last_sender,  // user|cskh|null
            'user_unread_count' => (int) $this->user_unread_count,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'messages' => SupportMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
