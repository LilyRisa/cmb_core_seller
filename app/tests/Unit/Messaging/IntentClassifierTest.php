<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Services\IntentClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test guardrail intent (SPEC-0024 §4.6) — pure shouldEscalate logic + circuit breaker.
 */
class IntentClassifierTest extends TestCase
{
    use RefreshDatabase;

    private function classifier(): IntentClassifier
    {
        return new IntentClassifier(app(AiAssistantRegistry::class));
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

    public function test_circuit_opens_after_repeated_failures_and_skips_provider(): void
    {
        Cache::flush();

        // provider 'flaky' (openai_compatible) active nhưng API luôn lỗi 500
        AiProvider::query()->create([
            'code' => 'flaky', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'k', 'default_model' => 'm', 'base_url' => 'https://api.deepseek.com',
        ]);
        Http::fake(['*' => Http::response('err', 500)]);

        $clf = app(IntentClassifier::class);

        // 5 lần lỗi đầu: vẫn gọi provider, trả escalate ('urgent')
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($clf->shouldEscalate($clf->classify(1, 'flaky', 'test')));
        }

        // Sau ngưỡng: circuit MỞ → KHÔNG gọi HTTP nữa nhưng vẫn escalate (an toàn)
        Http::fake(); // reset recorder
        $intent = $clf->classify(1, 'flaky', 'test');
        $this->assertTrue($clf->shouldEscalate($intent));
        Http::assertNothingSent();
    }
}
