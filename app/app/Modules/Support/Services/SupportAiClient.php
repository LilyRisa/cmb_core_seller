<?php

namespace CMBcoreSeller\Modules\Support\Services;

use Illuminate\Support\Facades\Http;

/**
 * Client AI TỰ CHỨA cho module Support — KHÔNG dùng bảng `ai_providers` hay
 * `AiAssistantRegistry`. Gọi thẳng API OpenAI-compatible bằng Laravel Http.
 *
 * Credentials đọc qua `system_setting()` (admin /admin/ai-support) → fallback
 * `config/support.php` (env). CHAT và EMBEDDING cấu hình ĐỘC LẬP: cho phép chat =
 * OpenRouter (không có embeddings) còn embedding = OpenAI/khác.
 *
 * Suy biến mượt: thiếu cấu hình ⇒ trả null (caller fallback keyword), KHÔNG ném.
 */
class SupportAiClient
{
    /** @return array{base_url:string, api_key:string, model:string} */
    private function chatConfig(): array
    {
        return [
            'base_url' => rtrim((string) system_setting('help_assistant.chat_base_url', config('support.assistant.chat.base_url', '')), '/'),
            'api_key' => (string) system_setting('help_assistant.chat_api_key', config('support.assistant.chat.api_key', '')),
            'model' => (string) system_setting('help_assistant.chat_model', config('support.assistant.chat.model', '')),
        ];
    }

    /** @return array{base_url:string, api_key:string, model:string} */
    private function embeddingConfig(): array
    {
        return [
            'base_url' => rtrim((string) system_setting('help_assistant.embedding_base_url', config('support.assistant.embedding.base_url', '')), '/'),
            'api_key' => (string) system_setting('help_assistant.embedding_api_key', config('support.assistant.embedding.api_key', '')),
            'model' => (string) system_setting('help_assistant.embedding_model', config('support.assistant.embedding.model', 'text-embedding-3-small')),
        ];
    }

    public function chatConfigured(): bool
    {
        $c = $this->chatConfig();

        return $c['base_url'] !== '' && $c['api_key'] !== '' && $c['model'] !== '';
    }

    public function embeddingConfigured(): bool
    {
        $c = $this->embeddingConfig();

        return $c['base_url'] !== '' && $c['api_key'] !== '' && $c['model'] !== '';
    }

    /**
     * Sinh câu trả lời chat. Trả nội dung text hoặc null (chưa cấu hình / lỗi).
     *
     * @param  list<array{role:string, content:string}>  $messages
     */
    public function chat(array $messages, int $maxTokens): ?string
    {
        if (! $this->chatConfigured()) {
            return null;
        }
        $c = $this->chatConfig();

        $res = Http::withToken($c['api_key'])
            ->timeout(30)->retry(2, 1000, throw: false)
            ->post($c['base_url'].'/v1/chat/completions', [
                'model' => $c['model'],
                'max_tokens' => $maxTokens,
                'messages' => $messages,
            ]);

        if (! $res->successful()) {
            return null;
        }
        $text = trim((string) $res->json('choices.0.message.content', ''));

        return $text !== '' ? $text : null;
    }

    /**
     * Embed 1 đoạn text → vector. Trả list<float> hoặc null (chưa cấu hình / lỗi /
     * provider không có /v1/embeddings — vd OpenRouter trả 404).
     *
     * @return list<float>|null
     */
    public function embed(string $text): ?array
    {
        if (! $this->embeddingConfigured()) {
            return null;
        }
        $c = $this->embeddingConfig();

        $res = Http::withToken($c['api_key'])
            ->timeout(30)->retry(2, 1000, throw: false)
            ->post($c['base_url'].'/v1/embeddings', [
                'model' => $c['model'],
                'input' => $text,
            ]);

        if (! $res->successful()) {
            return null;
        }
        $vector = array_map('floatval', (array) $res->json('data.0.embedding', []));

        return $vector !== [] ? $vector : null;
    }

    public function embeddingModel(): string
    {
        return $this->embeddingConfig()['model'];
    }
}
