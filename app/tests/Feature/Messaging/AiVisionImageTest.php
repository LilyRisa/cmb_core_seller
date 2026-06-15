<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\Claude\ClaudeConnector;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Vision (SPEC 2026-06-15): khách gửi ảnh → connector đính image block khi model có
 * vision; model thường / vision tắt giữ placeholder text.
 */
class AiVisionImageTest extends TestCase
{
    use RefreshDatabase;

    private function snapshotWithImage(string $url): ConversationSnapshot
    {
        return new ConversationSnapshot(
            conversationId: 1,
            provider: 'facebook_page',
            buyerName: 'Anh Khách',
            recentMessages: [
                ['direction' => 'inbound', 'kind' => 'image', 'body' => 'Cái này còn không shop?', 'sent_at' => null, 'image_urls' => [$url]],
            ],
        );
    }

    private function fakeClaude(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'Dạ còn hàng ạ.']],
            'usage' => ['input_tokens' => 5, 'output_tokens' => 3],
        ], 200)]);
        AiProvider::query()->create([
            'code' => 'claude', 'adapter' => 'anthropic', 'is_active' => true,
            'api_key' => 'sk-ant-x', 'default_model' => 'claude-opus-4-7',
        ]);
    }

    public function test_claude_sends_image_url_block_for_vision_model(): void
    {
        $this->fakeClaude();

        app(ClaudeConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'claude'),
            $this->snapshotWithImage('https://cdn.example.com/a.jpg'),
            null,
        );

        Http::assertSent(function ($req) {
            $content = $req->data()['messages'][0]['content'] ?? null;

            return is_array($content) && collect($content)->contains(
                fn ($b) => ($b['type'] ?? '') === 'image' && ($b['source']['type'] ?? '') === 'url'
                    && ($b['source']['url'] ?? '') === 'https://cdn.example.com/a.jpg',
            );
        });
    }

    public function test_claude_sends_base64_source_for_data_uri(): void
    {
        $this->fakeClaude();

        app(ClaudeConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'claude'),
            $this->snapshotWithImage('data:image/png;base64,QUJD'),
            null,
        );

        Http::assertSent(function ($req) {
            $content = $req->data()['messages'][0]['content'] ?? null;

            return is_array($content) && collect($content)->contains(
                fn ($b) => ($b['type'] ?? '') === 'image' && ($b['source']['type'] ?? '') === 'base64'
                    && ($b['source']['media_type'] ?? '') === 'image/png' && ($b['source']['data'] ?? '') === 'QUJD',
            );
        });
    }

    public function test_claude_skips_image_when_vision_disabled(): void
    {
        config(['ai.vision.enabled' => false]);
        $this->fakeClaude();

        app(ClaudeConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'claude'),
            $this->snapshotWithImage('https://cdn.example.com/a.jpg'),
            null,
        );

        Http::assertSent(fn ($req) => is_string($req->data()['messages'][0]['content'] ?? null));
    }

    public function test_openai_sends_image_url_block_for_vision_model(): void
    {
        Http::fake(['*/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Dạ còn hàng ạ.']]],
            'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 3],
        ], 200)]);
        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai', 'is_active' => true,
            'api_key' => 'sk-x', 'default_model' => 'gpt-4o',
        ]);

        app(OpenAiConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'openai'),
            $this->snapshotWithImage('https://cdn.example.com/a.jpg'),
            null,
        );

        Http::assertSent(function ($req) {
            $messages = $req->data()['messages'] ?? [];
            $userMsg = collect($messages)->firstWhere('role', 'user');
            $content = $userMsg['content'] ?? null;

            return is_array($content) && collect($content)->contains(
                fn ($b) => ($b['type'] ?? '') === 'image_url' && ($b['image_url']['url'] ?? '') === 'https://cdn.example.com/a.jpg',
            );
        });
    }
}
