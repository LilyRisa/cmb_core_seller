<?php

namespace CMBcoreSeller\Integrations\Ai\Contracts;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\AiReplyDTO;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\EmbeddingDTO;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;

/**
 * Contract mọi AI provider phải implement.
 *
 * Khác Messaging/Channels/Carriers/Payments: **config sống ở DB**
 * (`system_settings` group `ai_providers.<code>` — super-admin quản qua
 * `/admin/ai-providers`), không phải `config/integrations.php`. Tenant chỉ
 * chọn `tenant_settings.messaging.ai_provider_code` ∈ list `is_active=true`.
 *
 * Capabilities chuẩn:
 *   - 'reply.suggest'    — sinh draft cho NV duyệt
 *   - 'reply.auto'       — auto-mode (cần guardrail intent classify)
 *   - 'intent.classify'  — classify intent (guardrail cho auto-mode)
 *   - 'rag.training'     — index document (cần embedding)
 *   - 'embedding'        — sinh vector embedding cho RAG
 *
 * Provider không hỗ trợ ⇒ {@see UnsupportedOperation}.
 *
 * GOLDEN RULE (ADR-0018): core (`Modules\Messaging`) không bao giờ biết tên
 * provider cụ thể. Đổi vendor = thêm 1 class + 1 dòng register.
 */
interface AiAssistantConnector
{
    /** Stable code, e.g. 'claude' | 'openai' | 'gemini' | 'local_llm' | 'manual'. */
    public function code(): string;

    public function displayName(): string;

    /** @return array<string, bool> */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    /**
     * Sinh reply (suggest mode hoặc auto mode — `$ctx->meta['mode']`).
     * Body prompt đã qua `PiiRedactor` ở Messaging core.
     */
    public function generateReply(AiContext $ctx, ConversationSnapshot $conversation, ?KnowledgeBase $kb = null): AiReplyDTO;

    /**
     * Classify intent — `$candidates` là tập intent core muốn (mặc định
     * `['order_status','complaint','price','refund','urgent','smalltalk','other']`).
     * Provider không support ⇒ ném `UnsupportedOperation`.
     *
     * @param  list<string>  $candidates
     */
    public function classifyIntent(AiContext $ctx, string $text, array $candidates = []): IntentDTO;

    /**
     * Sinh embedding cho RAG indexing. Provider không support ⇒ `UnsupportedOperation`.
     * `KnowledgeIndexer` phải fallback sang provider khác (vd local_llm có embedding
     * khi Claude không có).
     */
    public function embed(AiContext $ctx, string $text): EmbeddingDTO;

    /**
     * Pricing snapshot — `cost_micro_vnd` per unit, để tính `ai_assistant_runs.cost_micro_vnd`.
     * Trả mảng `[{kind:'input_token'|'output_token'|'embedding_token', unit:1000, micro_vnd:N}, …]`.
     *
     * @return list<array{kind:string, unit:int, micro_vnd:int}>
     */
    public function pricing(): array;
}
