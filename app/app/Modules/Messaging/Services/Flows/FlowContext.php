<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;

/**
 * Dữ liệu 1 lần chạy node: hội thoại, run, nội dung inbound vừa nhận (nếu có).
 * `currentNodeId` được set bởi node executor khi duyệt steps — dùng bởi
 * SendButtonsStep để encode node id thật vào payload postback.
 */
final class FlowContext
{
    public function __construct(
        public readonly Conversation $conversation,
        public readonly FlowRun $run,
        public readonly ?string $inboundBody = null,
        public readonly ?string $currentNodeId = null,
    ) {}
}
