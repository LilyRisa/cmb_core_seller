<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed;
use CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiTranscribeTest extends TestCase
{
    use RefreshDatabase;

    private function groqProvider(): void
    {
        AiProvider::query()->create([
            'code' => 'groq', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'gsk-x', 'base_url' => 'https://api.groq.com/openai/v1',
            'default_model' => 'whisper-large-v3-turbo',
        ]);
    }

    public function test_transcribe_returns_text(): void
    {
        $this->groqProvider();
        Http::fake(['api.groq.com/*' => Http::response(['text' => 'xin chào shop'], 200)]);

        $out = app()->makeWith(OpenAiConnector::class, ['code' => 'groq'])->transcribeAudio(
            new AiContext(tenantId: 1, providerCode: 'groq'), 'RAWBYTES', 'audio/mpeg', 'voice.mp3');

        $this->assertSame('xin chào shop', $out);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/audio/transcriptions'));
    }

    public function test_transcribe_throws_on_http_error(): void
    {
        $this->groqProvider();
        Http::fake(['api.groq.com/*' => Http::response('nope', 500)]);

        $this->expectException(TranscriptionFailed::class);
        app()->makeWith(OpenAiConnector::class, ['code' => 'groq'])->transcribeAudio(
            new AiContext(tenantId: 1, providerCode: 'groq'), 'RAW', 'audio/mpeg', 'v.mp3');
    }

    public function test_capability_advertised(): void
    {
        $this->groqProvider();
        $this->assertTrue(app(OpenAiConnector::class)->supports('transcribe.audio'));
    }
}
