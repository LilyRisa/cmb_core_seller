<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ConversationCreated
{
    use Dispatchable;

    public function __construct(public int $conversationId) {}
}
