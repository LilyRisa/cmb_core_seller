<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

/**
 * Một loại node = một executor. Thêm loại node mới = thêm class implement
 * interface này + 1 dòng register — KHÔNG sửa FlowEngine (mở rộng không hardcode).
 */
interface NodeExecutor
{
    public function type(): string;

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult;
}
