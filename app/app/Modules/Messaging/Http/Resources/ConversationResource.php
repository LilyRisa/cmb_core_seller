<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Resources;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_account_id' => $this->channel_account_id,
            'provider' => $this->provider,
            'external_conversation_id' => $this->external_conversation_id,
            'buyer_external_id' => $this->buyer_external_id,
            'buyer_name' => $this->buyer_name,
            'buyer_avatar_url' => $this->buyer_avatar_url,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'snoozed_until' => $this->snoozed_until?->toIso8601String(),
            'unread_count' => (int) $this->unread_count,
            'message_count' => (int) $this->message_count,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_message_preview' => $this->last_message_preview,
            'last_inbound_at' => $this->last_inbound_at?->toIso8601String(),
            'last_outbound_at' => $this->last_outbound_at?->toIso8601String(),
            'assigned_user_id' => $this->assigned_user_id,
            'tags' => $this->tags ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
