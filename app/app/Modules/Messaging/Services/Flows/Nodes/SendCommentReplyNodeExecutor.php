<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\CommentReplyService;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

/**
 * Trả lời comment (công khai / nhắn riêng) qua CommentReplyService.
 * data: { text: string, target: { public: bool, private: bool } }
 */
class SendCommentReplyNodeExecutor implements NodeExecutor
{
    public function __construct(private CommentReplyService $commentReply) {}

    public function type(): string
    {
        return 'send_comment_reply';
    }

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

        $target = (array) ($node->data['target'] ?? ['public' => true, 'private' => false]);
        $this->commentReply->dispatch($ctx->conversation, $text, [
            'public' => (bool) ($target['public'] ?? false),
            'private' => (bool) ($target['private'] ?? false),
        ]);

        $sent[] = $node->id;
        $context = $ctx->run->context ?? [];
        $context['_sent'] = $sent;
        $ctx->run->update(['context' => $context]);

        return NodeResult::advance();
    }
}
