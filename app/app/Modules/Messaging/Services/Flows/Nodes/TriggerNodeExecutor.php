<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

class TriggerNodeExecutor implements NodeExecutor
{
    public function type(): string
    {
        return 'trigger';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        return NodeResult::advance();
    }
}
