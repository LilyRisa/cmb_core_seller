<?php

namespace CMBcoreSeller\Integrations\Ai\OpenAi;

use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\AiReplyDTO;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\EmbeddingDTO;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;

/**
 * OpenAI connector — STUB (S6). Khác Claude: OpenAI CÓ embedding API
 * (`text-embedding-3-*`) ⇒ capability `embedding`+`rag.training`=true (khi wire,
 * KnowledgeIndexer sẽ dùng OpenAI để embed kể cả khi reply provider là Claude).
 *
 * TODO (S6.1 — wire live API):
 *   - Credentials từ `AiProvider` record (code='openai'): api_key, base_url
 *     (cho Azure/proxy), default_model.
 *   - Chat: POST {base_url}/v1/chat/completions (Bearer key).
 *   - Embedding: POST {base_url}/v1/embeddings, model text-embedding-3-small (1536d).
 *   - Map usage → AiReplyDTO/EmbeddingDTO + cost theo pricing().
 */
class OpenAiConnector implements AiAssistantConnector
{
    public function code(): string
    {
        return 'openai';
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
            'rag.training' => true,
            'embedding' => true,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function generateReply(AiContext $ctx, ConversationSnapshot $conversation, ?KnowledgeBase $kb = null): AiReplyDTO
    {
        throw UnsupportedOperation::for($this->code(), 'generateReply (chưa wire HTTP client — xem TODO trong OpenAiConnector)');
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
