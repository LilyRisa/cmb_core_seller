<?php

namespace CMBcoreSeller\Integrations\Ai\DTO;

/**
 * Kết quả classify intent của 1 inbound message. `intent ∈ candidates` đã pass
 * tới provider (default: `order_status|complaint|price|refund|smalltalk|other`).
 *
 * Auto-mode guard: intent ∈ {complaint, refund, urgent, legal_threat, abuse}
 * ⇒ KHÔNG auto-reply, escalate human (SPEC-0024 §4.6).
 */
final readonly class IntentDTO
{
    public function __construct(
        public string $intent,
        public float $confidence = 1.0,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
