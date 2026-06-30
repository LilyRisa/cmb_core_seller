<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Steps;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;

/**
 * Bước gửi tin văn bản thuần. Tái dùng OutboundMessageService::queueText —
 * y hệt SendMessageNodeExecutor nhưng không idempotency (cursor do node executor
 * quản lý theo step index). Chuỗi rỗng ⇒ bỏ qua, không chặn luồng.
 *
 * config: { text: string }
 */
class SendTextStep implements StepExecutor
{
    public const TYPE = 'send_text';

    public function __construct(private OutboundMessageService $outbound) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function execute(FlowStep $step, FlowContext $ctx): StepResult
    {
        $text = trim((string) ($step->config['text'] ?? ''));

        if ($text === '') {
            return StepResult::done(); // bước rỗng ⇒ bỏ qua, không chặn luồng
        }

        $this->outbound->queueText($ctx->conversation, [
            'body' => $text,
            'sent_by_ai' => true,
            'kind' => 'text',
        ]);

        return StepResult::done();
    }
}
