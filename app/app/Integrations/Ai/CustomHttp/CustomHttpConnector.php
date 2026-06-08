<?php

namespace CMBcoreSeller\Integrations\Ai\CustomHttp;

use CMBcoreSeller\Integrations\Ai\Concerns\EstimatesAiCost;
use CMBcoreSeller\Integrations\Ai\Concerns\ReplyPersona;
use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\AiProviderRuntimeConfig;
use CMBcoreSeller\Integrations\Ai\DTO\AiReplyDTO;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\EmbeddingDTO;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Connector HTTP TÙY CHỈNH (adapter `custom_http` — SPEC-0026). Super-admin khai
 * báo endpoint + headers + body template + JSON path lấy câu trả lời ngay trong
 * /admin/ai-providers (cột `ai_providers.adapter_config`) — KHÔNG cần connector PHP mới.
 *
 * 1 connector phục vụ MỌI instance `custom_http`: registry inject `code`, connector
 * đọc `adapter_config` qua {@see AiProviderCredentials} (không import model Module).
 *
 * `request_template` (JSON) chứa placeholder:
 *   - {{model}} {{system}} {{last_user_message}} {{buyer_name}} {{api_key}}
 *       → chuỗi đã JSON-escape (KHÔNG kèm nháy ngoài — tác giả tự bọc "...").
 *   - {{messages_json}} → mảng JSON đầy đủ [{"role":"user","content":"..."}] (chèn không nháy).
 * Headers + URL: chỉ {{api_key}}/{{model}} thay THÔ (không JSON-escape).
 *
 * Capabilities: reply.suggest/reply.auto/intent.classify = true (classify gọi lại
 * cùng endpoint — bắt buộc để auto-mode không bị IntentClassifier escalate mặc định);
 * embedding/rag.training = false (RAG dùng keyword fallback).
 *
 * Prompt PHẢI đã qua PiiRedactor ở Messaging core trước khi tới đây (§8.4). Chỉ
 * hỗ trợ request/response JSON (v1).
 */
class CustomHttpConnector implements AiAssistantConnector
{
    use EstimatesAiCost;

    /** @var list<string> */
    private const DEFAULT_LABELS = ['order_status', 'complaint', 'price', 'refund', 'urgent', 'smalltalk', 'other'];

    public function __construct(private AiProviderCredentials $credentials, private string $code = 'custom_http') {}

    public function code(): string
    {
        return $this->code;
    }

    public function displayName(): string
    {
        return 'Custom HTTP';
    }

    public function capabilities(): array
    {
        return [
            'reply.suggest' => true,
            'reply.auto' => true,
            'intent.classify' => true,
            'rag.training' => false,
            'embedding' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function generateReply(AiContext $ctx, ConversationSnapshot $conversation, ?KnowledgeBase $kb = null): AiReplyDTO
    {
        $cfg = $this->config();
        $ac = $this->adapterConfig($cfg);

        $model = $ctx->model ?: ($cfg->defaultModel ?: '');
        $system = $this->buildSystemText($conversation, $kb, $ctx->systemPromptExtra);
        $messages = $this->buildMessages($conversation);
        $lastUser = $this->lastUserText($conversation);

        $body = $this->renderBody($ac, $this->bodyReplacements($model, $system, $messages, $lastUser, $conversation->buyerName ?? '', $cfg->apiKey));

        $startedAt = microtime(true);
        $response = $this->send($cfg, $ac, $body);
        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            throw new \RuntimeException('Custom HTTP AI '.$response->status().': '.Str::limit($response->body(), 200));
        }

        $json = (array) $response->json();
        $text = trim((string) data_get($json, (string) $ac['response_path'], ''));
        if ($text === '') {
            throw new \RuntimeException('Custom HTTP AI: không tìm thấy nội dung tại response_path ['.$ac['response_path'].'].');
        }

        $promptTokens = (int) data_get($json, (string) data_get($ac, 'usage.prompt_path', ''), 0);
        $completionTokens = (int) data_get($json, (string) data_get($ac, 'usage.completion_path', ''), 0);

        return new AiReplyDTO(
            body: $text,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            costMicroVnd: $this->estimateCostMicroVnd($cfg->pricing, $promptTokens, $completionTokens),
            durationMs: $durationMs,
            raw: ['status' => $response->status()],
        );
    }

    public function classifyIntent(AiContext $ctx, string $text, array $candidates = []): IntentDTO
    {
        $cfg = $this->config();
        $ac = $this->adapterConfig($cfg);
        $labels = $candidates !== [] ? $candidates : self::DEFAULT_LABELS;

        $model = $ctx->model ?: ($cfg->defaultModel ?: '');
        $system = 'Phân loại ý định tin nhắn khách hàng. Chỉ trả về DUY NHẤT 1 nhãn trong: '
            .implode(', ', $labels).'. Không giải thích.';
        $messages = [['role' => 'user', 'content' => $text]];

        $body = $this->renderBody($ac, $this->bodyReplacements($model, $system, $messages, $text, '', $cfg->apiKey));

        $response = $this->send($cfg, $ac, $body);
        if (! $response->successful()) {
            throw new \RuntimeException('Custom HTTP classify '.$response->status());
        }

        $out = strtolower(trim((string) data_get((array) $response->json(), (string) $ac['response_path'], '')));
        $intent = 'other';
        foreach ($labels as $label) {
            if ($out === $label || str_contains($out, (string) $label)) {
                $intent = (string) $label;
                break;
            }
        }

        return new IntentDTO(intent: $intent, confidence: 0.7);
    }

    public function embed(AiContext $ctx, string $text): EmbeddingDTO
    {
        throw UnsupportedOperation::for($this->code(), 'embed (custom_http chưa hỗ trợ embedding)');
    }

    public function pricing(): array
    {
        $cfg = $this->credentials->resolve($this->code());

        return $cfg === null ? [] : $cfg->pricing;
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function config(): AiProviderRuntimeConfig
    {
        $cfg = $this->credentials->resolve($this->code());
        if (! $cfg) {
            throw new ProviderNotConfigured('Custom HTTP provider chưa cấu hình.');
        }
        if (! $cfg->baseUrl) {
            throw new ProviderNotConfigured('Custom HTTP provider cần endpoint (base_url).');
        }

        return $cfg;
    }

    /** @return array<string,mixed> */
    private function adapterConfig(AiProviderRuntimeConfig $cfg): array
    {
        $ac = $cfg->adapterConfig;
        if (! is_string($ac['request_template'] ?? null) || trim((string) $ac['request_template']) === '') {
            throw new ProviderNotConfigured('Custom HTTP provider cần adapter_config.request_template.');
        }
        if (! is_string($ac['response_path'] ?? null) || trim((string) $ac['response_path']) === '') {
            throw new ProviderNotConfigured('Custom HTTP provider cần adapter_config.response_path.');
        }

        return $ac;
    }

    /**
     * @param  list<array{role:string,content:string}>  $messages
     * @return array<string,string>
     */
    private function bodyReplacements(string $model, string $system, array $messages, string $lastUser, string $buyerName, ?string $apiKey): array
    {
        return [
            '{{model}}' => $this->jsonEscape($model),
            '{{system}}' => $this->jsonEscape($system),
            '{{last_user_message}}' => $this->jsonEscape($lastUser),
            '{{buyer_name}}' => $this->jsonEscape($buyerName),
            '{{messages_json}}' => (string) (json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]'),
            '{{api_key}}' => $this->jsonEscape((string) $apiKey),
        ];
    }

    /**
     * @param  array<string,mixed>  $ac
     * @param  array<string,string>  $repl
     * @return array<string,mixed>
     */
    private function renderBody(array $ac, array $repl): array
    {
        $rendered = strtr((string) $ac['request_template'], $repl);
        $decoded = json_decode($rendered, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Custom HTTP request_template không tạo ra JSON hợp lệ sau khi điền placeholder.');
        }

        return $decoded;
    }

    /**
     * @param  array<string,mixed>  $ac
     * @param  array<string,mixed>  $body
     */
    private function send(AiProviderRuntimeConfig $cfg, array $ac, array $body): Response
    {
        $method = strtoupper((string) ($ac['method'] ?? 'POST'));
        $url = $this->interpolateRaw((string) $cfg->baseUrl, $cfg);

        // Custom HTTP v1 non-streaming: timeout TỔNG đủ rộng (dùng reply_timeout làm trần
        // chung cho cả reply & classify) + connect timeout ngắn (fail-fast) + retry transient.
        $request = Http::withHeaders($this->buildHeaders($ac, $cfg))
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->retry(1 + (int) config('ai.http.retries', 1), (int) config('ai.http.retry_backoff_ms', 1000), throw: false);

        return match ($method) {
            'GET' => $request->get($url),
            'PUT' => $request->put($url, $body),
            default => $request->post($url, $body),
        };
    }

    /**
     * @param  array<string,mixed>  $ac
     * @return array<string,string>
     */
    private function buildHeaders(array $ac, AiProviderRuntimeConfig $cfg): array
    {
        $headers = [];
        foreach ((array) ($ac['headers'] ?? []) as $key => $value) {
            $headers[(string) $key] = $this->interpolateRaw((string) $value, $cfg);
        }

        return $headers;
    }

    /** Thay {{api_key}}/{{model}} THÔ (cho header/URL — không phải ngữ cảnh JSON). */
    private function interpolateRaw(string $value, AiProviderRuntimeConfig $cfg): string
    {
        return strtr($value, [
            '{{api_key}}' => (string) ($cfg->apiKey ?? ''),
            '{{model}}' => (string) ($cfg->defaultModel ?? ''),
        ]);
    }

    /** JSON-escape 1 chuỗi để nhúng trong literal JSON (bỏ nháy ngoài). */
    private function jsonEscape(string $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? trim($encoded, '"') : '';
    }

    private function buildSystemText(ConversationSnapshot $c, ?KnowledgeBase $kb, ?string $extraSystem = null): string
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

        return $system;
    }

    /**
     * Map recentMessages → messages[] kiểu OpenAI (inbound→user, outbound→assistant),
     * KHÔNG kèm system (system riêng qua {{system}}). Bỏ assistant dẫn đầu.
     *
     * @return list<array{role:string,content:string}>
     */
    private function buildMessages(ConversationSnapshot $c): array
    {
        $messages = [];
        foreach ($c->recentMessages as $m) {
            $role = $m['direction'] === 'outbound' ? 'assistant' : 'user';
            $body = trim((string) ($m['body'] ?? '')) ?: '['.$m['kind'].']';
            $messages[] = ['role' => $role, 'content' => $body];
        }
        while ($messages !== [] && $messages[0]['role'] === 'assistant') {
            array_shift($messages);
        }
        if ($messages === []) {
            $messages[] = ['role' => 'user', 'content' => 'Khách vừa nhắn tin, hãy soạn lời chào hỗ trợ.'];
        }

        return $messages;
    }

    private function lastUserText(ConversationSnapshot $c): string
    {
        $last = '';
        foreach ($c->recentMessages as $m) {
            if ($m['direction'] !== 'outbound') {
                $last = trim((string) ($m['body'] ?? ''));
            }
        }

        return $last;
    }
}
