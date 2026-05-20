<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use CMBcoreSeller\Modules\Messaging\Events\Concerns\BroadcastsOnTenantChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ConversationCreated implements ShouldBroadcast
{
    use BroadcastsOnTenantChannel, Dispatchable;

    public function __construct(public int $conversationId) {}

    public function broadcastAs(): string
    {
        return 'conversation.created';
    }

    /** @return array<string,mixed> */
    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId];
    }
}
