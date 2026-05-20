<?php

namespace CMBcoreSeller\Integrations\Ai\DTO;

/**
 * Snapshot conversation cho AI sinh reply — last N messages + customer profile +
 * order context. Body đã qua `PiiRedactor` (08-security-and-privacy §6b §3).
 */
final readonly class ConversationSnapshot
{
    public function __construct(
        public int $conversationId,
        public string $provider,
        public ?string $buyerName = null,
        /** @var list<array{direction:string, kind:string, body:?string, sent_at:?string}> */
        public array $recentMessages = [],
        /** @var array<string, mixed>|null */
        public ?array $customerProfile = null,
        /** @var array<string, mixed>|null */
        public ?array $orderContext = null,
    ) {}
}
