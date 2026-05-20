<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát bởi `MessageIngestionService::ingest()` sau khi inbound message được
 * upsert idempotent. Listener:
 *   - `AutoReplyOnFirstMessage` (S5) — fire first_message rule nếu conversation mới
 *   - `NotifyAgentOnNewMessage` (S1 — placeholder, S5 wire vào notifications)
 *   - `BroadcastConversationUpdated` (S1 — wire khi Reverb ready)
 *   - `LinkConversationToCustomer` (S1 — qua CustomerProfileContract khi có buyer phone)
 *
 * `requiresHuman` set bởi engine (sau khi classify intent ở auto-mode S7) để
 * báo NV phải vào trả lời (escalate).
 */
class MessageReceived
{
    use Dispatchable;

    public function __construct(
        public int $messageId,
        public int $conversationId,
        public bool $requiresHuman = false,
    ) {}
}
