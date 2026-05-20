<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;

/**
 * Guardrail cho auto-mode (SPEC-0024 §4.6): phân loại intent tin nhắn để quyết
 * có cho AI TỰ GỬI hay phải escalate cho NV.
 *
 * Intent escalate (KHÔNG auto-send — phải có người): complaint, refund, urgent,
 * legal_threat, abuse. Còn lại (order_status, price, smalltalk, other) → cho auto-send.
 *
 * AN TOÀN MẶC ĐỊNH: classify lỗi / provider không hỗ trợ ⇒ coi như ESCALATE
 * (thà để NV xử lý còn hơn AI tự trả nhầm tin nhạy cảm).
 */
class IntentClassifier
{
    /** @var list<string> */
    public const ESCALATE = ['complaint', 'refund', 'urgent', 'legal_threat', 'abuse'];

    /** @var list<string> */
    public const ALL = ['order_status', 'price', 'complaint', 'refund', 'urgent', 'legal_threat', 'abuse', 'smalltalk', 'other'];

    public function __construct(private AiAssistantRegistry $registry) {}

    public function classify(int $tenantId, string $providerCode, string $text): IntentDTO
    {
        try {
            $connector = $this->registry->for($providerCode);
            if (! $connector->supports('intent.classify')) {
                return new IntentDTO(intent: 'urgent', confidence: 0.0); // escalate by default
            }

            return $connector->classifyIntent(new AiContext($tenantId, $providerCode), $text, self::ALL);
        } catch (\Throwable) {
            // Không phân loại được ⇒ escalate (an toàn).
            return new IntentDTO(intent: 'urgent', confidence: 0.0);
        }
    }

    public function shouldEscalate(IntentDTO $intent): bool
    {
        return in_array($intent->intent, self::ESCALATE, true);
    }
}
