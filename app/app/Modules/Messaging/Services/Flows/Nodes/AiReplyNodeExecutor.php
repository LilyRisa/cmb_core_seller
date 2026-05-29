<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use Illuminate\Support\Facades\Log;

/**
 * Node AI trả lời: sinh + gửi câu trả lời qua {@see AiSuggestionService::autoRespond}
 * (guardrail intent + PII redaction + ghi ai_assistant_runs dùng chung 1 nguồn với
 * auto-mode). Hai nhánh ra:
 *   - mặc định (handle null): AI đã trả lời được ⇒ đi tiếp.
 *   - 'handoff': intent nhạy cảm bị escalate, hết hạn mức, hoặc lỗi provider ⇒
 *     route sang bước cần người thật (autoRespond đã đánh `requires_human` khi escalate).
 *
 * Idempotent: đánh dấu node id vào _sent (không gọi AI 2 lần khi advance lặp).
 */
class AiReplyNodeExecutor implements NodeExecutor
{
    public function __construct(private AiSuggestionService $ai) {}

    public function type(): string
    {
        return 'ai_reply';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $sent = (array) ($ctx->run->context['_sent'] ?? []);
        if (in_array($node->id, $sent, true)) {
            return NodeResult::advance();
        }

        $handle = null;
        try {
            $res = $this->ai->autoRespond($ctx->conversation, (string) ($ctx->inboundBody ?? ''));
            $handle = $res['action'] === 'sent' ? null : 'handoff';
        } catch (\Throwable $e) {
            Log::warning('flow.ai_reply.failed', ['flow_run' => $ctx->run->id, 'error' => $e->getMessage()]);
            $handle = 'handoff';
        }

        $sent[] = $node->id;
        $context = $ctx->run->context ?? [];
        $context['_sent'] = $sent;
        $ctx->run->update(['context' => $context]);

        return NodeResult::advance($handle);
    }
}
