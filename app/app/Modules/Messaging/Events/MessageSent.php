<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MessageSent
{
    use Dispatchable;

    public function __construct(
        public int $messageId,
        public int $conversationId,
    ) {}
}
