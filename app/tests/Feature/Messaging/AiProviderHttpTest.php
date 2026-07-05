<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Claude\ClaudeConnector;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
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
            'code' => 'claude', 'adapter' => 'anthropic', 'is_active' => true,
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

        AiProvider::query()->create(['code' => 'claude', 'adapter' => 'anthropic', 'is_active' => true, 'api_key' => 'k', 'default_model' => 'claude-opus-4-7']);

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
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
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

    public function test_openai_strips_think_reasoning_block_from_reply(): void
    {
        // Model reasoning (DeepSeek-R1, Qwen-thinking…) chèn <think>…</think> vào content.
        Http::fake([
            'api.openai.com/*' => Http::response([
                'model' => 'deepseek-reasoner',
                'choices' => [['message' => [
                    'content' => "<think>Khách hỏi thời gian giao, mình nên trấn an...</think>\n\nDạ đơn của anh/chị giao trong 2-3 ngày ạ.",
                ], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 10],
            ], 200),
        ]);

        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-oai', 'default_model' => 'deepseek-reasoner',
        ]);

        $reply = app(OpenAiConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'openai'),
            $this->snapshot(),
            null,
        );

        $this->assertStringNotContainsString('<think>', $reply->body);
        $this->assertStringNotContainsString('trấn an', $reply->body);
        $this->assertSame('Dạ đơn của anh/chị giao trong 2-3 ngày ạ.', $reply->body);
    }

    public function test_openai_strips_dangling_closing_think_tag(): void
    {
        // Template chat tự chèn thẻ mở ⇒ model chỉ sinh "suy luận…</think>câu trả lời".
        Http::fake([
            'api.openai.com/*' => Http::response([
                'model' => 'qwen-thinking',
                'choices' => [['message' => [
                    'content' => 'Khách đang sốt ruột, mình xác nhận lịch giao.</think>Dạ shop giao trong 2-3 ngày ạ.',
                ], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 3],
            ], 200),
        ]);

        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-oai', 'default_model' => 'qwen-thinking',
        ]);

        $reply = app(OpenAiConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'openai'),
            $this->snapshot(),
            null,
        );

        $this->assertSame('Dạ shop giao trong 2-3 ngày ạ.', $reply->body);
    }

    public function test_custom_http_strips_reasoning_block(): void
    {
        Http::fake([
            'llm.example.com/*' => Http::response([
                'data' => ['reply' => ['text' => '<thinking>nội bộ</thinking>Dạ shop hỗ trợ anh/chị ngay ạ.']],
                'usage' => ['in' => 1, 'out' => 1],
            ], 200),
        ]);
        $this->customHttpProvider();

        $reply = app(AiAssistantRegistry::class)->for('my-llm')->generateReply(
            new AiContext(tenantId: 1, providerCode: 'my-llm'),
            $this->snapshot(),
            null,
        );

        $this->assertSame('Dạ shop hỗ trợ anh/chị ngay ạ.', $reply->body);
    }

    public function test_openai_base_url_with_v1_suffix_is_not_doubled(): void
    {
        // base_url theo chuẩn OpenAI SDK gồm sẵn '/v1' ⇒ KHÔNG được nhân đôi thành /v1/v1.
        Http::fake([
            'llm.chiasegpu.vn/*' => Http::response([
                'model' => 'gx/gpt-5.3-codex',
                'choices' => [['message' => ['content' => 'Dạ shop hỗ trợ anh/chị ngay ạ.'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 3],
            ], 200),
        ]);

        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-x', 'default_model' => 'gx/gpt-5.3-codex',
            'base_url' => 'https://llm.chiasegpu.vn/v1',
        ]);

        app(OpenAiConnector::class)->generateReply(
            new AiContext(tenantId: 1, providerCode: 'openai'),
            $this->snapshot(),
            null,
        );

        Http::assertSent(fn ($req) => $req->url() === 'https://llm.chiasegpu.vn/v1/chat/completions');
    }

    public function test_openai_appends_global_system_prompt_to_reply_but_not_classify(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'ok'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);
        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-oai', 'default_model' => 'gpt-4o-mini',
        ]);

        $ctx = new AiContext(tenantId: 1, providerCode: 'openai', systemPromptExtra: 'LUÔN giới thiệu shop ABC ở đầu.');
        app(OpenAiConnector::class)->generateReply($ctx, $this->snapshot(), null);
        app(OpenAiConnector::class)->classifyIntent($ctx, 'đơn của tôi đâu rồi');

        // Reply PHẢI chèn prompt chung 'shop ABC' (nhận diện qua nội dung system).
        Http::assertSent(fn ($req) => str_contains((string) ($req->data()['messages'][0]['content'] ?? ''), 'shop ABC'));
        // Classify KHÔNG được chèn prompt chung (giữ guardrail) — system là câu "Phân loại ý định".
        Http::assertSent(fn ($req) => str_contains((string) ($req->data()['messages'][0]['content'] ?? ''), 'Phân loại ý định')
            && ! str_contains((string) ($req->data()['messages'][0]['content'] ?? ''), 'shop ABC'));
    }

    public function test_openai_classify_uses_wide_max_tokens_for_reasoning_models(): void
    {
        // max_tokens=8 cắt cụt model suy luận (Minimax-M3 sinh <think>… trước nhãn) ⇒ luôn "other".
        // Trần phải đủ rộng (config classify_max_tokens) để reasoning xong rồi mới tới nhãn.
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'price'], 'finish_reason' => 'stop']],
            ], 200),
        ]);
        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-oai', 'default_model' => 'gpt-4o-mini',
        ]);

        app(OpenAiConnector::class)->classifyIntent(new AiContext(tenantId: 1, providerCode: 'openai'), 'giá bao nhiêu');

        Http::assertSent(fn ($req) => str_contains($req->url(), 'chat/completions')
            && ($req->data()['max_tokens'] ?? 0) >= 256);
    }

    public function test_openai_embed_returns_vector(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [['embedding' => [0.1, 0.2, 0.3]]],
                'usage' => ['total_tokens' => 5],
            ], 200),
        ]);

        AiProvider::query()->create(['code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true, 'api_key' => 'sk-oai', 'default_model' => 'gpt-4o-mini']);

        $embedding = app(OpenAiConnector::class)->embed(new AiContext(tenantId: 1, providerCode: 'openai'), 'chính sách đổi trả');

        $this->assertSame(3, $embedding->dimension);
        $this->assertSame([0.1, 0.2, 0.3], $embedding->vector);
        $this->assertSame(5, $embedding->tokenCount);
    }

    private function customHttpProvider(): void
    {
        AiProvider::query()->create([
            'code' => 'my-llm', 'adapter' => 'custom_http', 'is_active' => true,
            'api_key' => 'secret-key', 'base_url' => 'https://llm.example.com/chat',
            'default_model' => 'my-model',
            'adapter_config' => [
                'method' => 'POST',
                'headers' => ['Authorization' => 'Bearer {{api_key}}'],
                'request_template' => '{"model":"{{model}}","system":"{{system}}","messages":{{messages_json}}}',
                'response_path' => 'data.reply.text',
                'usage' => ['prompt_path' => 'usage.in', 'completion_path' => 'usage.out'],
            ],
            'pricing' => [
                ['kind' => 'input_token', 'unit' => 1000, 'micro_vnd' => 100],
                ['kind' => 'output_token', 'unit' => 1000, 'micro_vnd' => 500],
            ],
        ]);
    }

    public function test_custom_http_renders_template_and_parses_path(): void
    {
        Http::fake([
            'llm.example.com/*' => Http::response([
                'data' => ['reply' => ['text' => 'Dạ shop hỗ trợ anh/chị ngay ạ.']],
                'usage' => ['in' => 12, 'out' => 8],
            ], 200),
        ]);
        $this->customHttpProvider();

        $reply = app(AiAssistantRegistry::class)->for('my-llm')->generateReply(
            new AiContext(tenantId: 1, providerCode: 'my-llm'),
            $this->snapshot(),
            null,
        );

        $this->assertStringContainsString('hỗ trợ', $reply->body);
        $this->assertSame(12, $reply->promptTokens);
        $this->assertSame(8, $reply->completionTokens);
        $this->assertSame(5, $reply->costMicroVnd); // round(12/1000*100)+round(8/1000*500)=1+4

        Http::assertSent(function ($req) {
            $body = $req->data();

            return str_contains($req->url(), 'llm.example.com/chat')
                && $req->hasHeader('Authorization', 'Bearer secret-key')
                && $body['model'] === 'my-model'
                && is_array($body['messages'])
                // Persona dùng chung (ReplyPersona) — kiểm quy tắc ưu tiên hội thoại.
                && str_contains((string) $body['system'], 'ƯU TIÊN nội dung ĐOẠN HỘI THOẠI');
        });
    }

    public function test_custom_http_classify_returns_label(): void
    {
        Http::fake([
            'llm.example.com/*' => Http::response(['data' => ['reply' => ['text' => 'refund']]], 200),
        ]);
        $this->customHttpProvider();

        $intent = app(AiAssistantRegistry::class)->for('my-llm')
            ->classifyIntent(new AiContext(tenantId: 1, providerCode: 'my-llm'), 'Cho em hoàn tiền đơn này');

        $this->assertSame('refund', $intent->intent);
    }

    public function test_custom_http_supports_intent_classify_for_auto_mode(): void
    {
        $this->customHttpProvider();

        // intent.classify=true là điều kiện để auto-mode không bị IntentClassifier escalate mặc định.
        $this->assertTrue(app(AiAssistantRegistry::class)->for('my-llm')->supports('intent.classify'));
    }

    public function test_custom_http_missing_template_throws_not_configured(): void
    {
        AiProvider::query()->create([
            'code' => 'broken-llm', 'adapter' => 'custom_http', 'is_active' => true,
            'base_url' => 'https://llm.example.com/chat', // thiếu adapter_config.request_template/response_path
        ]);

        $this->expectException(ProviderNotConfigured::class);
        app(AiAssistantRegistry::class)->for('broken-llm')->generateReply(
            new AiContext(tenantId: 1, providerCode: 'broken-llm'),
            $this->snapshot(),
            null,
        );
    }

    public function test_analyze_images_uses_configured_max_tokens(): void
    {
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-4o']);
        config()->set('ai.vision.max_tokens', 2048);

        Http::fake([
            '*/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => '{"match":1}']]]], 200),
        ]);

        AiProvider::query()->create([
            'code' => 'openai', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-x', 'base_url' => 'https://api.openai.com', 'default_model' => 'gpt-4o',
        ]);

        app(OpenAiConnector::class)->analyzeImages(
            new AiContext(tenantId: 1, providerCode: 'openai'),
            ['data:image/png;base64,AAAA'],
            'pick one',
        );

        Http::assertSent(fn ($req) => ($req->data()['max_tokens'] ?? null) === 2048);
    }
}
