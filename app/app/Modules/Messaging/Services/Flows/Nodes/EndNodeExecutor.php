<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

class EndNodeExecutor implements NodeExecutor
{
    public function type(): string { return 'end'; }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        return NodeResult::end();
    }
}
