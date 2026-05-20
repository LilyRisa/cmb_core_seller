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
 * Contract test live HTTP wiring Claude + OpenAI (SPEC-0024 S6.1) — Http::fake.
 * Xác minh request shape (endpoint/headers/body), parse response (text + usage),
 * tính cost theo pricing, và map lỗi → exception. Live call cần API key thật.
 */
class AiProviderHttpTest extends TestCase
{
    use RefreshDatabase;

    private function snapshot(): ConversationSnapshot
    {
        return new ConversationSnapshot(
            conversationId: 1,
            provider: 'facebook_page',
            buyerName: 'Anh Khách',
            recentMessages: [
                ['direction' => 'inbound', 'kind' => 'text', 'body' => 'Đơn của em bao giờ giao?', 'sent_at' => null],
            ],
        );
    }

    public function test_claude_generate_reply_parses_text_and_usage(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'model' => 'claude-opus-4-7',
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Dạ đơn của anh/chị dự kiến giao trong 2-3 ngày ạ.']],
                'usage' => ['input_tokens' => 12, 'output_tokens' => 8],
            ], 200),
        ]);

        AiProvider::query()->create([
            'code' => 'claude', 'is_active' => true,
            'api_key' => 'sk-ant-xxx', 'default_model' => 'claude-opus-4-7',
            'pricing' => [
                ['kind' => 'input_token', 'unit' => 1000, 'micro_vnd' => 100],
                ['kind' => 'output_token', 'unit' => 1000, 'micro_vnd' => 500],
            ],
        ]);

        $reply = app(ClaudeConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'claude'),
            $this->snapshot(),
            null,
        );

        $this->assertStringContainsString('2-3 ngày', $reply->body);
        $this->assertSame(12, $reply->promptTokens);
        $this->assertSame(8, $reply->completionTokens);
        $this->assertSame(5, $reply->costMicroVnd); // round(12/1000*100)+round(8/1000*500)=1+4

        Http::assertSent(function ($req) {
            $body = $req->data();

            return str_contains($req->url(), '/v1/messages')
                && $req->hasHeader('x-api-key', 'sk-ant-xxx')
                && $req->hasHeader('anthropic-version', '2023-06-01')
                && $body['model'] === 'claude-opus-4-7'
                && ($body['system'][0]['cache_control']['type'] ?? null) === 'ephemeral'
                && $body['messages'][0]['role'] === 'user';
        });
    }

    public function test_claude_maps_http_error_to_exception(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        AiProvider::query()->create(['code' => 'claude', 'is_active' => true, 'api_key' => 'k', 'default_model' => 'claude-opus-4-7']);

        $this->expectException(\RuntimeException::class);
        app(ClaudeConnector::class)->generateReply(new AiContext(tenantId: 1, providerCode: 'claude'), $this->snapshot(), null);
    }

    public function test_openai_generate_reply_parses_choices(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'Dạ shop hỗ trợ anh/chị ngay ạ.'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 10],
            ], 200),
        ]);

        AiProvider::query()->create([
            'code' => 'openai', 'is_active' => true,
            'api_key' => 'sk-oai', 'default_model' => 'gpt-4o-mini',
        ]);

        $reply = app(OpenAiConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'openai'),
            $this->snapshot(),
            null,
        );

        $this->assertStringContainsString('shop hỗ trợ', $reply->body);
        $this->assertSame(20, $reply->promptTokens);
        $this->assertSame(10, $reply->completionTokens);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/chat/completions')
            && $req->hasHeader('Authorization', 'Bearer sk-oai')
            && $req->data()['messages'][0]['role'] === 'system');
    }

    public function test_openai_embed_returns_vector(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [['embedding' => [0.1, 0.2, 0.3]]],
                'usage' => ['total_tokens' => 5],
            ], 200),
        ]);

        AiProvider::query()->create(['code' => 'openai', 'is_active' => true, 'api_key' => 'sk-oai', 'default_model' => 'gpt-4o-mini']);

        $embedding = app(OpenAiConnector::class)->embed(new AiContext(tenantId: 1, providerCode: 'openai'), 'chính sách đổi trả');

        $this->assertSame(3, $embedding->dimension);
        $this->assertSame([0.1, 0.2, 0.3], $embedding->vector);
        $this->assertSame(5, $embedding->tokenCount);
    }
}
