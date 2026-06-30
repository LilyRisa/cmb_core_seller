<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Steps;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;
use Illuminate\Support\Facades\Log;

/**
 * Bước gửi tin có nút bấm rồi CHỜ buyer bấm (postback). Mirror
 * SendInteractiveNodeExecutor: capability gate qua InteractiveMessagingConnector +
 * supports('outbound.interactive') — không instanceof tên sàn. Không hỗ trợ ⇒
 * fail('interactive_unsupported') + log (không ném). Trả wait() để node executor
 * giữ luồng chờ.
 *
 * Payload postback encode FlowPostbackPayload với step.id làm node placeholder —
 * Task 3 (node executor) sẽ truyền node_id thật qua khi gọi step này.
 *
 * config: { text: string, buttons: [ { id, label, type?:'postback'|'url', url? } ] }
 * Phase 2A: step này PHẢI là bước CUỐI mỗi node (enforced bởi validator Task 4).
 */
class SendButtonsStep implements StepExecutor
{
    public const TYPE = 'send_buttons';

    public function __construct(
        private OutboundMessageService $outbound,
        private MessagingRegistry $registry,
    ) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function execute(FlowStep $step, FlowContext $ctx): StepResult
    {
        // Kiểm năng lực theo TÊN NĂNG LỰC (interface + capability map), KHÔNG tên sàn.
        $provider = $ctx->conversation->provider;
        $connector = $this->registry->has($provider) ? $this->registry->for($provider) : null;

        if (! $connector instanceof InteractiveMessagingConnector || ! $connector->supports('outbound.interactive')) {
            Log::warning('flow.step.interactive_unsupported', [
                'provider' => $provider,
                'flow_run' => $ctx->run->id,
                'step_id' => $step->id,
            ]);

            return StepResult::fail('interactive_unsupported');
        }

        $text = trim((string) ($step->config['text'] ?? ''));
        $buttons = [];

        foreach ((array) ($step->config['buttons'] ?? []) as $b) {
            $b = (array) $b;
            $label = (string) ($b['label'] ?? $b['title'] ?? '');
            if ($label === '') {
                continue;
            }

            if (((string) ($b['type'] ?? 'postback')) === 'url' && ! empty($b['url'])) {
                $buttons[] = ['type' => 'url', 'title' => $label, 'url' => (string) $b['url']];
            } else {
                $handle = (string) ($b['id'] ?? $label);
                // Payload encode dùng step.id — Task 3 node executor override với node_id thật
                // (Phase 2A buttons là bước cuối nên resume theo node, không cần step index).
                $buttons[] = ['type' => 'postback', 'title' => $label, 'payload' => FlowPostbackPayload::encode($step->id, $handle)];
            }
        }

        // Bước rỗng (không text & không nút) ⇒ bỏ qua, không chặn luồng.
        if ($text === '' && $buttons === []) {
            return StepResult::done();
        }

        $this->outbound->queueInteractive($ctx->conversation, ['text' => $text, 'buttons' => $buttons], [
            'sent_by_ai' => true,
            'flow_id' => $ctx->run->flow_id,
            'flow_run_id' => $ctx->run->id,
        ]);

        // Dừng chờ postback — handle nút do node executor quyết định khi resume.
        return StepResult::wait();
    }
}
