<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use CMBcoreSeller\Modules\Messaging\Events\Concerns\BroadcastsOnTenantChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcast
{
    use BroadcastsOnTenantChannel, Dispatchable;

    public function __construct(
        public int $messageId,
        public int $conversationId,
    ) {}

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /** @return array<string,mixed> */
    public function broadcastWith(): array
    {
        return ['message_id' => $this->messageId, 'conversation_id' => $this->conversationId];
    }
}
