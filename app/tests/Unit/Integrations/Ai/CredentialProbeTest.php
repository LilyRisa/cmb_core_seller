<?php

namespace Tests\Unit\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\CredentialProbe;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CredentialProbeTest extends TestCase
{
    public function test_probe_chat_openai_compatible_reports_ok_on_success(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => 'pong']]]],
                200,
            ),
        ]);

        $result = (new CredentialProbe)->probeChat('openai_compatible', null, 'sk-test', 'gpt-4o-mini');

        $this->assertTrue($result['ok']);
    }

    public function test_probe_chat_missing_api_key_fails_without_any_http_call(): void
    {
        Http::fake();

        $result = (new CredentialProbe)->probeChat('openai_compatible', null, null, 'gpt-4o-mini');

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_probe_chat_anthropic_surfaces_provider_error_message(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response(
                ['error' => ['message' => 'invalid x-api-key']],
                401,
            ),
        ]);

        $result = (new CredentialProbe)->probeChat('anthropic', null, 'bad-key', 'claude-opus-4-7');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('invalid x-api-key', (string) $result['message']);
    }

    public function test_probe_chat_rejects_unprobeable_adapter(): void
    {
        Http::fake();

        $result = (new CredentialProbe)->probeChat('custom_http', null, 'sk-test', 'model');

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_probe_embedding_reports_dimension_on_success(): void
    {
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response(
                ['data' => [['embedding' => [0.1, 0.2, 0.3]]]],
                200,
            ),
        ]);

        $result = (new CredentialProbe)->probeEmbedding(null, 'sk-test', 'text-embedding-3-small');

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('dim 3', (string) $result['message']);
    }

    public function test_probe_embedding_missing_model_fails_without_http_call(): void
    {
        Http::fake();

        $result = (new CredentialProbe)->probeEmbedding(null, 'sk-test', null);

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }
}
