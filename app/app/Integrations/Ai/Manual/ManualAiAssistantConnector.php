<?php

namespace CMBcoreSeller\Integrations\Ai\Manual;

use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\AiReplyDTO;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\EmbeddingDTO;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;

/**
 * "manual" AI provider — KHÔNG gọi LLM thật. Trả reply deterministic dựa
 * trên last inbound message. Dùng để:
 *   1. Test pipeline E2E của AI suggestion mà không tốn LLM credit.
 *   2. Fallback an toàn khi tenant chưa chọn provider thật.
 *
 * Cost = 0, intent = 'other' (luôn). Embedding ném `UnsupportedOperation` —
 * RAG indexing không dùng được với provider này.
 */
class ManualAiAssistantConnector implements AiAssistantConnector
{
    public function __construct(private string $code = 'manual') {}

    public function code(): string
    {
        return $this->code;
    }

    public function displayName(): string
    {
        return 'Manual (test)';
    }

    public function capabilities(): array
    {
        return [
            'reply.suggest' => true,
            'reply.auto' => false,    // không dùng cho auto-mode (response không thông minh)
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
        $lastInbound = null;
        foreach (array_reverse($conversation->recentMessages) as $m) {
            if (($m['direction'] ?? null) === 'inbound') {
                $lastInbound = $m;
                break;
            }
        }

        $body = $lastInbound && ! empty($lastInbound['body'])
            ? 'Cảm ơn anh/chị đã liên hệ. Em đã nhận tin nhắn và sẽ phản hồi sớm.'
            : 'Em chào anh/chị, em có thể hỗ trợ gì ạ?';

        return new AiReplyDTO(
            body: $body,
            promptTokens: 0,
            completionTokens: mb_strlen($body),
            costMicroVnd: 0,
            durationMs: 0,
            raw: ['provider' => 'manual', 'deterministic' => true],
        );
    }

    public function classifyIntent(AiContext $ctx, string $text, array $candidates = []): IntentDTO
    {
        // Heuristic keyword đơn giản (deterministic) — đủ để test guardrail auto-mode
        // mà không gọi LLM. Provider thật override bằng classify đúng nghĩa.
        $t = mb_strtolower($text);
        $intent = match (true) {
            str_contains($t, 'hoàn tiền') || str_contains($t, 'refund') || str_contains($t, 'trả lại tiền') => 'refund',
            str_contains($t, 'khiếu nại') || str_contains($t, 'tố cáo') || str_contains($t, 'kiện') => 'complaint',
            str_contains($t, 'gấp') || str_contains($t, 'khẩn') || str_contains($t, 'urgent') => 'urgent',
            default => 'other',
        };

        return new IntentDTO(intent: $intent, confidence: 0.6);
    }

    public function embed(AiContext $ctx, string $text): EmbeddingDTO
    {
        throw UnsupportedOperation::for($this->code(), 'embed');
    }

    public function pricing(): array
    {
        return [];   // free
    }
}
