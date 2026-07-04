<?php

namespace CMBcoreSeller\Integrations\Ai\OpenAi;

use CMBcoreSeller\Integrations\Ai\Concerns\EstimatesAiCost;
use CMBcoreSeller\Integrations\Ai\Concerns\ReplyPersona;
use CMBcoreSeller\Integrations\Ai\Concerns\SanitizesReasoning;
use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Integrations\Ai\Contracts\AudioTranscriber;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\AiProviderRuntimeConfig;
use CMBcoreSeller\Integrations\Ai\DTO\AiReplyDTO;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\EmbeddingDTO;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI connector — Chat Completions + Embeddings (raw HTTP, không SDK).
 *
 * Chat : POST {base_url|https://api.openai.com}/v1/chat/completions (Bearer key)
 * Embed: POST {base_url}/v1/embeddings
 * Model: super-admin nhập `default_model` (BẮT BUỘC — không hardcode để tránh
 *        đoán sai model OpenAI). Embedding model lấy từ ctx.meta hoặc mặc định
 *        text-embedding-3-small.
 *
 * `base_url` cho phép trỏ Azure OpenAI / proxy tương thích OpenAI.
 * OpenAI CÓ embedding ⇒ capability rag.training/embedding=true: KnowledgeIndexer
 * có thể dùng provider này để embed kể cả khi reply provider là Claude.
 *
 * Adapter `openai_compatible`: dùng cho OpenAI, DeepSeek, Qwen (DashScope compat),
 * OpenRouter, Gemini (v1beta/openai)… phân biệt qua base_url + api_key + default_model per-instance.
 */
class OpenAiConnector implements AiAssistantConnector, AudioTranscriber
{
    use EstimatesAiCost;
    use SanitizesReasoning;

    public function __construct(private AiProviderCredentials $credentials, private string $code = 'openai') {}

    public function code(): string
    {
        return $this->code;
    }

    public function displayName(): string
    {
        return 'OpenAI';
    }

    public function capabilities(): array
    {
        return [
            'reply.suggest' => true,
            'reply.auto' => true,
            'intent.classify' => true,
            'text.generate' => true,
            'rag.training' => true,
            'embedding' => true,
            'vision.analyze' => true,  // re-rank visual search (model vision)
            'transcribe.audio' => true,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function generateReply(AiContext $ctx, ConversationSnapshot $conversation, ?KnowledgeBase $kb = null): AiReplyDTO
    {
        $cfg = $this->config();
        $model = $ctx->model ?: $cfg->defaultModel;
        if (! $model) {
            throw new ProviderNotConfigured('OpenAI provider cần default_model.');
        }

        $startedAt = microtime(true);
        $response = Http::withToken($cfg->apiKey)
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post($this->base($cfg).'/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => $ctx->maxTokens ?: 1024,
                'messages' => $this->buildMessages($conversation, $kb, $ctx->systemPromptExtra, $cfg->visionVerified),
            ]);

        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $error = (array) $response->json('error');
            throw new \RuntimeException('OpenAI API '.$response->status().': '.($error['message'] ?? $response->body()));
        }

        $text = (string) $response->json('choices.0.message.content', '');
        $usage = (array) $response->json('usage', []);
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);

        return new AiReplyDTO(
            body: $this->stripReasoning($text),
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            costMicroVnd: $this->estimateCostMicroVnd($cfg->pricing, $promptTokens, $completionTokens),
            durationMs: $durationMs,
            raw: ['model' => $response->json('model'), 'finish_reason' => $response->json('choices.0.finish_reason'), 'usage' => $usage],
        );
    }

    public function generateText(AiContext $ctx, string $prompt, ?string $system = null): AiReplyDTO
    {
        $cfg = $this->config();
        $model = $ctx->model ?: $cfg->defaultModel;
        if (! $model) {
            throw new ProviderNotConfigured('OpenAI provider cần default_model.');
        }

        $messages = [];
        if ($system !== null && trim($system) !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $startedAt = microtime(true);
        $response = Http::withToken($cfg->apiKey)
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post($this->base($cfg).'/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => $ctx->maxTokens ?: 1024,
                'messages' => $messages,
            ]);

        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $error = (array) $response->json('error');
            throw new \RuntimeException('OpenAI API '.$response->status().': '.($error['message'] ?? $response->body()));
        }

        $usage = (array) $response->json('usage', []);
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);

        return new AiReplyDTO(
            body: $this->stripReasoning((string) $response->json('choices.0.message.content', '')),
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            costMicroVnd: $this->estimateCostMicroVnd($cfg->pricing, $promptTokens, $completionTokens),
            durationMs: $durationMs,
            raw: ['model' => $response->json('model'), 'usage' => $usage],
        );
    }

    public function classifyIntent(AiContext $ctx, string $text, array $candidates = []): IntentDTO
    {
        $cfg = $this->config();
        $model = $ctx->model ?: $cfg->defaultModel;
        if (! $model) {
            throw new ProviderNotConfigured('OpenAI provider cần default_model.');
        }

        $labels = $candidates !== [] ? $candidates
            : ['order_status', 'complaint', 'price', 'refund', 'urgent', 'smalltalk', 'other'];

        $response = Http::withToken($cfg->apiKey)
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.classify_timeout', 30))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post($this->base($cfg).'/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => 8,
                'messages' => [
                    ['role' => 'system', 'content' => 'Phân loại ý định tin nhắn khách. Chỉ trả về 1 nhãn trong: '.implode(', ', $labels).'.'],
                    ['role' => 'user', 'content' => $text],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI classify '.$response->status());
        }

        $intent = strtolower($this->stripReasoning((string) $response->json('choices.0.message.content', 'other')));
        if (! in_array($intent, $labels, true)) {
            $intent = 'other';
        }

        return new IntentDTO(intent: $intent, confidence: 0.8);
    }

    public function embed(AiContext $ctx, string $text): EmbeddingDTO
    {
        $cfg = $this->config();
        $model = (string) ($ctx->meta['embedding_model'] ?? 'text-embedding-3-small');

        $response = Http::withToken($cfg->apiKey)
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.embed_timeout', 90))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post($this->base($cfg).'/v1/embeddings', [
                'model' => $model,
                'input' => $text,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI embed '.$response->status());
        }

        $vector = array_map('floatval', (array) $response->json('data.0.embedding', []));
        $tokens = (int) $response->json('usage.total_tokens', 0);

        return new EmbeddingDTO(
            vector: $vector,
            dimension: count($vector),
            model: $model,
            tokenCount: $tokens,
        );
    }

    public function analyzeImages(AiContext $ctx, array $images, string $instruction): string
    {
        $cfg = $this->config();
        $model = $ctx->model ?: $cfg->defaultModel;
        if (! $model) {
            throw new ProviderNotConfigured('OpenAI provider cần default_model.');
        }

        $parts = [['type' => 'text', 'text' => $instruction]];
        foreach (array_values(array_filter($images, 'is_string')) as $img) {
            // OpenAI nhận cả https lẫn data-URI ở image_url.url.
            $parts[] = ['type' => 'image_url', 'image_url' => ['url' => (string) $img]];
        }

        $response = Http::withToken($cfg->apiKey)
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false)
            ->post($this->base($cfg).'/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => (int) config('ai.vision.max_tokens', 2048),
                'messages' => [['role' => 'user', 'content' => $parts]],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI analyzeImages '.$response->status());
        }

        return $this->stripReasoning((string) $response->json('choices.0.message.content', ''));
    }

    public function transcribeAudio(AiContext $ctx, string $bytes, string $mime, ?string $filename = null): string
    {
        $cfg = $this->config();
        $model = $ctx->model ?: $cfg->defaultModel;
        if (! $model) {
            throw new ProviderNotConfigured('OpenAI provider cần default_model (STT).');
        }

        $response = Http::withToken($cfg->apiKey)
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->attach('file', $bytes, $filename ?: 'audio.mp3', ['Content-Type' => $mime])
            ->post($this->base($cfg).'/v1/audio/transcriptions', [
                'model' => $model,
                'response_format' => 'json',
            ]);

        if (! $response->successful()) {
            throw TranscriptionFailed::http($this->code(), $response->status());
        }

        return trim((string) $response->json('text', ''));
    }

    public function pricing(): array
    {
        return $this->config(false)->pricing;
    }

    private function config(bool $requireKey = true): AiProviderRuntimeConfig
    {
        $cfg = $this->credentials->resolve($this->code());
        if (! $cfg || ($requireKey && ! $cfg->apiKey)) {
            throw new ProviderNotConfigured('OpenAI provider chưa cấu hình api_key.');
        }

        return $cfg;
    }

    private function base(AiProviderRuntimeConfig $cfg): string
    {
        $base = rtrim($cfg->baseUrl ?: 'https://api.openai.com', '/');

        // Chuẩn OpenAI SDK: base_url thường đã gồm '/v1' (vd https://host/v1). Các
        // call site tự thêm '/v1/...' ⇒ bỏ '/v1' ở đuôi để không nhân đôi (/v1/v1).
        if (str_ends_with($base, '/v1')) {
            $base = substr($base, 0, -3);
        }

        return $base;
    }

    /** @return list<array{role:string, content:string|list<array<string,mixed>>}> */
    private function buildMessages(ConversationSnapshot $c, ?KnowledgeBase $kb, ?string $extraSystem = null, bool $vision = false): array
    {
        $system = ReplyPersona::instructions($c, $extraSystem);
        $ctxText = ReplyPersona::contextBlock($c);
        if ($ctxText !== '') {
            $system .= "\n\n".$ctxText;
        }
        $kbText = ReplyPersona::knowledgeBlock($kb);
        if ($kbText !== '') {
            $system .= "\n\n".$kbText;
        }

        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($c->recentMessages as $m) {
            $role = ($m['direction'] ?? '') === 'outbound' ? 'assistant' : 'user';
            $images = $vision ? array_values(array_filter((array) ($m['image_urls'] ?? []), 'is_string')) : [];

            if ($images !== []) {
                $body = trim((string) ($m['body'] ?? ''));
                $content = [];
                if ($body !== '') {
                    $content[] = ['type' => 'text', 'text' => $body];
                }
                foreach ($images as $url) {
                    // OpenAI nhận cả https lẫn data-URI ở image_url.url.
                    $content[] = ['type' => 'image_url', 'image_url' => ['url' => (string) $url]];
                }
                $messages[] = ['role' => $role, 'content' => $content];

                continue;
            }

            $body = trim((string) ($m['body'] ?? '')) ?: '['.($m['kind'] ?? 'media').']';
            $messages[] = ['role' => $role, 'content' => $body];
        }
        if (count($messages) === 1) {
            $messages[] = ['role' => 'user', 'content' => 'Khách vừa nhắn tin, hãy soạn lời chào hỗ trợ.'];
        }

        return $messages;
    }
}
