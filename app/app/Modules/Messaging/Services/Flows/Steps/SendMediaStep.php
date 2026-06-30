<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Steps;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;

/**
 * Bước gửi đa phương tiện (ảnh / video / file). Tái dùng
 * OutboundMessageService::queueMedia — y hệt đường dẫn trong SendMessageNodeExecutor
 * nhưng 1 attachment mỗi step (mỗi step là 1 bước riêng). Idempotency do node executor
 * quản lý theo step index. storage_path rỗng ⇒ bỏ qua.
 *
 * config: { kind: 'image'|'video'|'file', attachment: { storage_path, mime?, filename?, size_bytes? } }
 */
class SendMediaStep implements StepExecutor
{
    public const TYPE = 'send_media';

    public function __construct(private OutboundMessageService $outbound) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function execute(FlowStep $step, FlowContext $ctx): StepResult
    {
        $attachment = (array) ($step->config['attachment'] ?? []);
        $storagePath = (string) ($attachment['storage_path'] ?? '');

        if ($storagePath === '') {
            return StepResult::done(); // bước rỗng ⇒ bỏ qua
        }

        $kind = (string) ($step->config['kind'] ?? $attachment['kind'] ?? 'file');

        $this->outbound->queueMedia($ctx->conversation, [
            'kind' => $kind,
            'storage_path' => $storagePath,
            'mime' => isset($attachment['mime']) ? (string) $attachment['mime'] : null,
            'filename' => isset($attachment['filename']) ? (string) $attachment['filename'] : null,
            'size_bytes' => isset($attachment['size_bytes']) ? (int) $attachment['size_bytes'] : null,
        ], [
            'sent_by_ai' => true,
            'flow_id' => $ctx->run->flow_id,
            'flow_run_id' => $ctx->run->id,
        ]);

        return StepResult::done();
    }
}
