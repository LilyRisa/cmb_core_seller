<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Steps;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;

/**
 * Một loại step = một executor. Thêm loại step mới = thêm class implement
 * interface này + 1 dòng register — KHÔNG sửa FlowEngine (mở rộng không hardcode).
 */
interface StepExecutor
{
    public function type(): string;

    public function execute(FlowStep $step, FlowContext $ctx): StepResult;
}
