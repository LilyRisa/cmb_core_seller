<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Modules\Messaging\Services\IntentClassifier;
use Tests\TestCase;

/**
 * Test guardrail intent (SPEC-0024 §4.6) — pure shouldEscalate logic.
 */
class IntentClassifierTest extends TestCase
{
    private function classifier(): IntentClassifier
    {
        return new IntentClassifier(app(\CMBcoreSeller\Integrations\Ai\AiAssistantRegistry::class));
    }

    public function test_escalates_sensitive_intents(): void
    {
        $c = $this->classifier();
        foreach (['complaint', 'refund', 'urgent', 'legal_threat', 'abuse'] as $intent) {
            $this->assertTrue($c->shouldEscalate(new IntentDTO($intent, 0.9)), "[$intent] phải escalate");
        }
    }

    public function test_does_not_escalate_safe_intents(): void
    {
        $c = $this->classifier();
        foreach (['order_status', 'price', 'smalltalk', 'other'] as $intent) {
            $this->assertFalse($c->shouldEscalate(new IntentDTO($intent, 0.9)), "[$intent] không escalate");
        }
    }
}
