<?php

namespace CMBcoreSeller\Integrations\Ai\Claude;

use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\AiReplyDTO;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\EmbeddingDTO;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;

/**
 * Anthropic Claude connector — STUB (S6). Capabilities khai báo đầy đủ để
 * super-admin thấy provider trong `/admin/ai-providers`, nhưng các call thật
 * ném `UnsupportedOperation` cho tới khi wire HTTP client.
 *
 * TODO (S6.1 — wire live API):
 *   - Đọc credentials từ `AiProvider` record (code='claude'): api_key, default_model.
 *     Tránh import Messaging model ở đây — inject qua 1 credentials-resolver bound
 *     ở IntegrationsServiceProvider (giữ Integrations\Ai độc lập Module).
 *   - POST https://api.anthropic.com/v1/messages (header `x-api-key`,
 *     `anthropic-version: 2023-06-01`), model `claude-...`, system prompt CSKH +
 *     KnowledgeBase chunks, messages = ConversationSnapshot.recentMessages.
 *   - Map usage.input_tokens/output_tokens → AiReplyDTO + cost theo pricing().
 *   - Claude KHÔNG có embedding API ⇒ `embed()` giữ UnsupportedOperation
 *     (KnowledgeIndexer fallback provider khác / keyword search).
 *   - Prompt đã qua PiiRedactor ở Messaging core trước khi tới đây.
 */
class ClaudeConnector implements AiAssistantConnector
{
    public function code(): string
    {
        return 'claude';
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
            'rag.training' => false,   // không có embedding API riêng
            'embedding' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function generateReply(AiContext $ctx, ConversationSnapshot $conversation, ?KnowledgeBase $kb = null): AiReplyDTO
    {
        throw UnsupportedOperation::for($this->code(), 'generateReply (chưa wire HTTP client — xem TODO trong ClaudeConnector)');
    }

    public function classifyIntent(AiContext $ctx, string $text, array $candidates = []): IntentDTO
    {
        throw UnsupportedOperation::for($this->code(), 'classifyIntent');
    }

    public function embed(AiContext $ctx, string $text): EmbeddingDTO
    {
        throw UnsupportedOperation::for($this->code(), 'embed');
    }

    public function pricing(): array
    {
        return [];
    }
}
