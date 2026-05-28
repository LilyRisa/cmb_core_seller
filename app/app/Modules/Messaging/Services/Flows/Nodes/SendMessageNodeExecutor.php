<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;

/**
 * Gửi tin DM (text) qua OutboundMessageService (audit + 24h window guard chạy như
 * tin NV gửi). Chống gửi lại: đánh dấu node id vào run.context._sent.
 */
class SendMessageNodeExecutor implements NodeExecutor
{
    public function __construct(private OutboundMessageService $outbound) {}

    public function type(): string { return 'send_message'; }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $text = trim((string) ($node->data['text'] ?? ''));
        if ($text === '') {
            return NodeResult::advance();
        }

        $sent = (array) ($ctx->run->context['_sent'] ?? []);
        if (in_array($node->id, $sent, true)) {
            return NodeResult::advance();
        }

        $this->outbound->queueText($ctx->conversation, [
            'body' => $text,
            'sent_by_ai' => true,
            'kind' => 'text',
        ]);

        $sent[] = $node->id;
        $context = $ctx->run->context ?? [];
        $context['_sent'] = $sent;
        $ctx->run->update(['context' => $context]);

        return NodeResult::advance();
    }
}
