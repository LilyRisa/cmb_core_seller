<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MessageFailed
{
    use Dispatchable;

    public function __construct(
        public int $messageId,
        public int $conversationId,
        public string $failureCode,
    ) {}
}
