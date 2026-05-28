<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

class WaitReplyNodeExecutor implements NodeExecutor
{
    public function type(): string
    {
        return 'wait_reply';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        // Có inbound mới (resume) ⇒ đi tiếp; chưa có ⇒ chờ.
        return $ctx->inboundBody !== null ? NodeResult::advance() : NodeResult::wait();
    }
}
