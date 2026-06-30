<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\FlowStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;

/**
 * Gửi tin DM: text và/hoặc đa phương tiện (ảnh/video/âm thanh/file). Mỗi media đã
 * upload sẵn lúc dựng kịch bản (node.data.attachments[].storage_path); gửi text
 * trước rồi từng media là 1 tin riêng (Send API: 1 tin = 1 đính kèm). Qua
 * OutboundMessageService ⇒ audit + 24h window guard đồng nhất. Chống gửi lại:
 * đánh dấu node id vào run.context._sent.
 *
 * **Steps branch (additive):** nếu KEY `node.data.steps` tồn tại và là mảng, dùng nhánh
 * steps dù mảng rỗng (rỗng → không gửi gì, trả advance(null)). Chỉ dùng nhánh cũ khi
 * hoàn toàn không có key `steps` (node chưa bao giờ mở bằng editor mới). Idempotency
 * theo cursor `_step_sent[node.id]`.
 *
 * data (cũ): { text?: string, attachments?: [ { kind, storage_path, mime?, filename?, size_bytes? } ] }
 * data (mới): { steps: [ { id, type, ...config } ] }
 */
class SendMessageNodeExecutor implements NodeExecutor
{
    public function __construct(
        private OutboundMessageService $outbound,
        private StepExecutorRegistry $stepRegistry,
    ) {}

    public function type(): string
    {
        return 'send_message';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        // Steps branch — KEY `steps` có mặt (kể cả mảng rỗng) → dùng nhánh steps.
        // Mảng rỗng nghĩa là user đã xoá hết bước; không gửi gì, không dùng lại text cũ.
        if (array_key_exists('steps', $node->data) && is_array($node->data['steps'])) {
            $rawSteps = array_values(array_filter(
                $node->data['steps'],
                fn ($s) => is_array($s) && ! empty($s['type']) && ! empty($s['id']),
            ));
            if ($rawSteps === []) {
                return NodeResult::advance(null); // steps rỗng → không gửi gì
            }

            return $this->executeSteps($rawSteps, $node, $ctx);
        }

        // ── Nhánh cũ: text + attachments thẳng vào data (giữ nguyên byte-for-byte) ──

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

    /**
     * Duyệt steps[], idempotency theo cursor `_step_sent[node.id]`.
     *
     * @param  array<int,array<string,mixed>>  $rawSteps
     */
    private function executeSteps(array $rawSteps, FlowNode $node, FlowContext $ctx): NodeResult
    {
        $context = $ctx->run->context ?? [];
        $stepSent = (array) ($context['_step_sent'] ?? []);
        $cursor = (int) ($stepSent[$node->id] ?? 0);

        // Build step context mang node id thật — SendButtonsStep dùng để encode payload
        // postback với đúng node id (phục vụ stale-guard ở AdvanceFlowOnPostback).
        $stepCtx = new FlowContext($ctx->conversation, $ctx->run, $ctx->inboundBody, $node->id);

        foreach ($rawSteps as $index => $stepArr) {
            if ($index < $cursor) {
                continue; // Already sent — skip (idempotent).
            }

            $step = FlowStep::fromArray($stepArr);

            if (! $this->stepRegistry->has($step->type)) {
                return NodeResult::fail("unknown_step_type:{$step->type}");
            }

            $result = $this->stepRegistry->for($step->type)->execute($step, $stepCtx);

            if ($result->isFail()) {
                return NodeResult::fail($result->error() ?? 'step_failed');
            }

            // Advance cursor — step đã gửi/đạt tới (kể cả wait).
            $cursor = $index + 1;
            $stepSent[$node->id] = $cursor;
            $context['_step_sent'] = $stepSent;
            $ctx->run->update(['context' => $context]);

            if ($result->isWait()) {
                return NodeResult::wait();
            }

            // isDone → tiếp bước kế.
        }

        return NodeResult::advance(null);
    }
}
