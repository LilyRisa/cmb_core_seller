<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;

/**
 * Gửi tin DM: text và/hoặc đa phương tiện (ảnh/video/âm thanh/file). Mỗi media đã
 * upload sẵn lúc dựng kịch bản (node.data.attachments[].storage_path); gửi text
 * trước rồi từng media là 1 tin riêng (Send API: 1 tin = 1 đính kèm). Qua
 * OutboundMessageService ⇒ audit + 24h window guard đồng nhất. Chống gửi lại:
 * đánh dấu node id vào run.context._sent.
 *
 * data: { text?: string, attachments?: [ { kind, storage_path, mime?, filename?, size_bytes? } ] }
 */
class SendMessageNodeExecutor implements NodeExecutor
{
    public function __construct(private OutboundMessageService $outbound) {}

    public function type(): string
    {
        return 'send_message';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $text = trim((string) ($node->data['text'] ?? ''));
        $attachments = array_values(array_filter(
            (array) ($node->data['attachments'] ?? []),
            fn ($a) => is_array($a) && ! empty($a['storage_path']),
        ));

        if ($text === '' && $attachments === []) {
            return NodeResult::advance(); // node rỗng ⇒ bỏ qua, không chặn luồng
        }

        $sent = (array) ($ctx->run->context['_sent'] ?? []);
        if (in_array($node->id, $sent, true)) {
            return NodeResult::advance(); // idempotent
        }

        if ($text !== '') {
            $this->outbound->queueText($ctx->conversation, [
                'body' => $text,
                'sent_by_ai' => true,
                'kind' => 'text',
            ]);
        }

        foreach ($attachments as $a) {
            $a = (array) $a;
            $this->outbound->queueMedia($ctx->conversation, [
                'kind' => (string) ($a['kind'] ?? 'file'),
                'storage_path' => (string) $a['storage_path'],
                'mime' => isset($a['mime']) ? (string) $a['mime'] : null,
                'filename' => isset($a['filename']) ? (string) $a['filename'] : null,
                'size_bytes' => isset($a['size_bytes']) ? (int) $a['size_bytes'] : null,
            ], [
                'sent_by_ai' => true,
                'flow_id' => $ctx->run->flow_id,
                'flow_run_id' => $ctx->run->id,
                'node_id' => $node->id,
            ]);
        }

        $sent[] = $node->id;
        $context = $ctx->run->context ?? [];
        $context['_sent'] = $sent;
        $ctx->run->update(['context' => $context]);

        return NodeResult::advance();
    }
}
