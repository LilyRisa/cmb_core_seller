<?php

namespace CMBcoreSeller\Integrations\Ai\Claude;

use CMBcoreSeller\Integrations\Ai\Concerns\EstimatesAiCost;
use CMBcoreSeller\Integrations\Ai\Concerns\ReplyPersona;
use CMBcoreSeller\Integrations\Ai\Concerns\SanitizesReasoning;
use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\AiReplyDTO;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\EmbeddingDTO;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;
use Illuminate\Support\Facades\Http;

/**
 * Anthropic Claude connector — Messages API (raw HTTP qua Laravel Http, không SDK).
 *
 * Endpoint: POST {base_url|https://api.anthropic.com}/v1/messages
 * Headers : x-api-key, anthropic-version: 2023-06-01
 * Body    : {model, max_tokens, system[], messages[]}
 * Model   : super-admin nhập `default_model` (claude-opus-4-7 | claude-sonnet-4-6 |
 *           claude-haiku-4-5); fallback claude-opus-4-7.
 *
 * Prompt PHẢI đã qua `PiiRedactor` ở Messaging core trước khi tới đây (§8.4).
 * Credentials đọc qua {@see AiProviderCredentials} (không import Module model).
 *
 * Claude KHÔNG có embedding API ⇒ `embed()` ném UnsupportedOperation (RAG dùng
 * provider khác có embedding, hoặc keyword fallback).
 */
class ClaudeConnector implements AiAssistantConnector
{
    use EstimatesAiCost;
    use SanitizesReasoning;

    private const API_VERSION = '2023-06-01';

    private const DEFAULT_MODEL = 'claude-opus-4-7';

    public function __construct(private AiProviderCredentials $credentials, private string $code = 'claude') {}

    public function code(): string
    {
        return $this->code;
    }

    public function displayName(): string
    {
        return 'Anthropic Claude';
    }

    public function capabilities(): array
    {
        return [
            'reply.suggest' => true,
            'reply.auto' => true,
            'intent.classify' => true,
            'text.generate' => true,
            'rag.training' => false,   // không có embedding API riêng
            'embedding' => false,
            'vision.analyze' => true,  // re-rank visual search (model vision)
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function generateReply(AiContext $ctx, ConversationSnapshot $conversation, ?KnowledgeBase $kb = null): AiReplyDTO
    {
        $cfg = $this->credentials->resolve($this->code());
        if (! $cfg || ! $cfg->apiKey) {
            throw new ProviderNotConfigured('Claude provider chưa cấu hình api_key.');
        }

        $model = $ctx->model ?: ($cfg->defaultModel ?: self::DEFAULT_MODEL);
        $maxTokens = $ctx->maxTokens ?: 1024; // reply CSKH ngắn — non-streaming an toàn

        $startedAt = microtime(true);
        $response = Http::withHeaders([
            'x-api-key' => $cfg->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            // retry chỉ kích hoạt trên ConnectionException (timeout/đứt) — KHÔNG tự ném để map lỗi bên dưới
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post(rtrim($cfg->baseUrl ?: 'https://api.anthropic.com', '/').'/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $this->buildSystem($conversation, $kb, $ctx->systemPromptExtra),
                'messages' => $this->buildMessages($conversation, $this->visionEnabled($model)),
            ]);

        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $error = (array) $response->json('error');
            throw new \RuntimeException('Claude API '.$response->status().': '.($error['message'] ?? $response->body()));
        }

        $text = '';
        foreach ((array) $response->json('content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        $usage = (array) $response->json('usage', []);
        $promptTokens = (int) ($usage['input_tokens'] ?? 0);
        $completionTokens = (int) ($usage['output_tokens'] ?? 0);

        return new AiReplyDTO(
            body: $this->stripReasoning($text),
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            costMicroVnd: $this->estimateCostMicroVnd($cfg->pricing, $promptTokens, $completionTokens),
            durationMs: $durationMs,
            raw: ['model' => $response->json('model'), 'stop_reason' => $response->json('stop_reason'), 'usage' => $usage],
        );
    }

    public function generateText(AiContext $ctx, string $prompt, ?string $system = null): AiReplyDTO
    {
        $cfg = $this->credentials->resolve($this->code());
        if (! $cfg || ! $cfg->apiKey) {
            throw new ProviderNotConfigured('Claude provider chưa cấu hình api_key.');
        }

        $startedAt = microtime(true);
        $response = Http::withHeaders([
            'x-api-key' => $cfg->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post(rtrim($cfg->baseUrl ?: 'https://api.anthropic.com', '/').'/v1/messages', [
                'model' => $ctx->model ?: ($cfg->defaultModel ?: self::DEFAULT_MODEL),
                'max_tokens' => $ctx->maxTokens ?: 1024,
                'system' => [['type' => 'text', 'text' => $system ?: 'Bạn là trợ lý viết nội dung thương mại điện tử bằng tiếng Việt.']],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $error = (array) $response->json('error');
            throw new \RuntimeException('Claude API '.$response->status().': '.($error['message'] ?? $response->body()));
        }

        $text = '';
        foreach ((array) $response->json('content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        $usage = (array) $response->json('usage', []);
        $promptTokens = (int) ($usage['input_tokens'] ?? 0);
        $completionTokens = (int) ($usage['output_tokens'] ?? 0);

        return new AiReplyDTO(
            body: $this->stripReasoning($text),
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            costMicroVnd: $this->estimateCostMicroVnd($cfg->pricing, $promptTokens, $completionTokens),
            durationMs: $durationMs,
            raw: ['model' => $response->json('model'), 'usage' => $usage],
        );
    }

    public function classifyIntent(AiContext $ctx, string $text, array $candidates = []): IntentDTO
    {
        $cfg = $this->credentials->resolve($this->code());
        if (! $cfg || ! $cfg->apiKey) {
            throw new ProviderNotConfigured('Claude provider chưa cấu hình api_key.');
        }

        $labels = $candidates !== [] ? $candidates
            : ['order_status', 'complaint', 'price', 'refund', 'urgent', 'smalltalk', 'other'];

        $response = Http::withHeaders([
            'x-api-key' => $cfg->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.classify_timeout', 30))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post(rtrim($cfg->baseUrl ?: 'https://api.anthropic.com', '/').'/v1/messages', [
                'model' => $ctx->model ?: ($cfg->defaultModel ?: self::DEFAULT_MODEL),
                'max_tokens' => 16,
                'system' => [[
                    'type' => 'text',
                    'text' => 'Bạn phân loại ý định tin nhắn khách hàng. Chỉ trả về DUY NHẤT 1 nhãn trong: '
                        .implode(', ', $labels).'. Không giải thích.',
                ]],
                'messages' => [['role' => 'user', 'content' => $text]],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Claude classify '.$response->status());
        }

        $out = '';
        foreach ((array) $response->json('content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $out .= (string) ($block['text'] ?? '');
            }
        }
        $intent = strtolower($this->stripReasoning($out));
        if (! in_array($intent, $labels, true)) {
            $intent = 'other';
        }

        return new IntentDTO(intent: $intent, confidence: 0.8);
    }

    public function embed(AiContext $ctx, string $text): EmbeddingDTO
    {
        throw UnsupportedOperation::for($this->code(), 'embed (Claude không có embedding API)');
    }

    public function analyzeImages(AiContext $ctx, array $images, string $instruction): string
    {
        $cfg = $this->credentials->resolve($this->code());
        if (! $cfg || ! $cfg->apiKey) {
            throw new ProviderNotConfigured('Claude provider chưa cấu hình api_key.');
        }
        $model = $ctx->model ?: ($cfg->defaultModel ?: self::DEFAULT_MODEL);
        if (! $this->visionEnabled($model)) {
            throw UnsupportedOperation::for($this->code(), 'analyzeImages (model không vision)');
        }

        $content = [['type' => 'text', 'text' => $instruction]];
        foreach (array_values(array_filter($images, 'is_string')) as $img) {
            $content[] = ['type' => 'image', 'source' => $this->imageSource((string) $img)];
        }

        $response = Http::withHeaders([
            'x-api-key' => $cfg->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post(rtrim($cfg->baseUrl ?: 'https://api.anthropic.com', '/').'/v1/messages', [
                'model' => $model,
                'max_tokens' => 300,
                'messages' => [['role' => 'user', 'content' => $content]],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Claude analyzeImages '.$response->status());
        }

        $text = '';
        foreach ((array) $response->json('content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        return $this->stripReasoning($text);
    }

    public function pricing(): array
    {
        return $this->credentials->resolve($this->code())?->pricing ?? [];
    }

    /**
     * System prompt: phần hướng dẫn cố định (cache_control ephemeral — prefix
     * caching) + KB chunks (biến động, không cache).
     *
     * @return list<array<string,mixed>>
     */
    private function buildSystem(ConversationSnapshot $c, ?KnowledgeBase $kb, ?string $extraSystem = null): array
    {
        $system = [[
            'type' => 'text',
            'text' => ReplyPersona::instructions($c, $extraSystem),
            'cache_control' => ['type' => 'ephemeral'],
        ]];

        $ctxText = ReplyPersona::contextBlock($c);
        if ($ctxText !== '') {
            $system[] = ['type' => 'text', 'text' => $ctxText];
        }

        $kbText = ReplyPersona::knowledgeBlock($kb);
        if ($kbText !== '') {
            $system[] = ['type' => 'text', 'text' => $kbText];
        }

        return $system;
    }

    /**
     * Map recentMessages → Anthropic messages[]. inbound→user, outbound→assistant.
     * Đảm bảo bắt đầu bằng `user` (API yêu cầu); bỏ assistant dẫn đầu.
     *
     * Khi `$vision` và tin có `image_urls`, gửi content dạng block (text + image) để
     * model nhìn ảnh; ngược lại gửi content string như cũ.
     *
     * @return list<array{role:string, content:string|list<array<string,mixed>>}>
     */
    private function buildMessages(ConversationSnapshot $c, bool $vision = false): array
    {
        $messages = [];
        foreach ($c->recentMessages as $m) {
            $role = ($m['direction'] ?? '') === 'outbound' ? 'assistant' : 'user';
            $body = trim((string) ($m['body'] ?? ''));
            $images = $vision ? array_values(array_filter((array) ($m['image_urls'] ?? []), 'is_string')) : [];

            if ($images !== []) {
                $content = [];
                if ($body !== '') {
                    $content[] = ['type' => 'text', 'text' => $body];
                }
                foreach ($images as $url) {
                    $content[] = ['type' => 'image', 'source' => $this->imageSource((string) $url)];
                }
                $messages[] = ['role' => $role, 'content' => $content];

                continue;
            }

            if ($body === '') {
                $body = '['.($m['kind'] ?? 'media').']';
            }
            $messages[] = ['role' => $role, 'content' => $body];
        }

        // Bỏ assistant dẫn đầu (API yêu cầu first=user).
        while ($messages !== [] && $messages[0]['role'] === 'assistant') {
            array_shift($messages);
        }
        if ($messages === []) {
            $messages[] = ['role' => 'user', 'content' => 'Khách vừa nhắn tin, hãy soạn lời chào hỗ trợ.'];
        }

        return $messages;
    }

    /**
     * Source ảnh cho Anthropic: `https://…` → type=url; `data:…;base64,…` → type=base64.
     *
     * @return array<string,mixed>
     */
    private function imageSource(string $url): array
    {
        if (str_starts_with($url, 'data:') && preg_match('#^data:([^;]+);base64,(.*)$#s', $url, $mm) === 1) {
            return ['type' => 'base64', 'media_type' => $mm[1] !== '' ? $mm[1] : 'image/jpeg', 'data' => $mm[2]];
        }

        return ['type' => 'url', 'url' => $url];
    }

    /** Model hiện tại có khả năng vision? Ủy quyền cho VisionModelGate (nguồn sự thật chung). */
    private function visionEnabled(string $model): bool
    {
        return \CMBcoreSeller\Integrations\Ai\Support\VisionModelGate::enabledFor($model);
    }
}
