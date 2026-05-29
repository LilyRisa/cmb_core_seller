<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;
use Illuminate\Support\Facades\Log;

/**
 * Gửi tin có nút bấm rồi CHỜ buyer bấm (postback). Mỗi nút `postback` được gán
 * payload mã hoá {node_id, handle=button.id}; edge ra dùng `sourceHandle=button.id`
 * ⇒ engine resume đúng nhánh khi có postback. Nút `url` mở web (không cần edge).
 *
 * Kiểm NĂNG LỰC provider trước (capability map) — không hỗ trợ ⇒ node fail + log
 * (không ném vỡ luồng, không spam). Chống gửi lại: đánh dấu node id vào _sent.
 *
 * data: { text: string, buttons: [ { id, label, type?:'postback'|'url', url? } ] }
 */
class SendInteractiveNodeExecutor implements NodeExecutor
{
    public function __construct(
        private OutboundMessageService $outbound,
        private MessagingRegistry $registry,
    ) {}

    public function type(): string
    {
        return 'send_buttons';
    }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        // Đã gửi rồi (advance lặp / re-run) ⇒ giữ trạng thái chờ postback.
        $sent = (array) ($ctx->run->context['_sent'] ?? []);
        if (in_array($node->id, $sent, true)) {
            return NodeResult::wait();
        }

        // Kiểm năng lực theo TÊN NĂNG LỰC (interface + capability map), KHÔNG phải tên
        // sàn — connector phải vừa implement InteractiveMessagingConnector vừa bật cờ.
        $provider = $ctx->conversation->provider;
        $connector = $this->registry->has($provider) ? $this->registry->for($provider) : null;
        if (! $connector instanceof InteractiveMessagingConnector || ! $connector->supports('outbound.interactive')) {
            Log::warning('flow.interactive_unsupported', ['provider' => $provider, 'flow_run' => $ctx->run->id]);

            return NodeResult::fail('interactive_unsupported');
        }

        $text = trim((string) ($node->data['text'] ?? ''));
        $buttons = [];
        foreach ((array) ($node->data['buttons'] ?? []) as $b) {
            $b = (array) $b;
            $label = (string) ($b['label'] ?? $b['title'] ?? '');
            if ($label === '') {
                continue;
            }
            if (((string) ($b['type'] ?? 'postback')) === 'url' && ! empty($b['url'])) {
                $buttons[] = ['type' => 'url', 'title' => $label, 'url' => (string) $b['url']];
            } else {
                $handle = (string) ($b['id'] ?? $label);
                $buttons[] = ['type' => 'postback', 'title' => $label, 'payload' => FlowPostbackPayload::encode($node->id, $handle)];
            }
        }

        // Node rỗng (không text & không nút) ⇒ bỏ qua, không chặn luồng.
        if ($text === '' && $buttons === []) {
            return NodeResult::advance();
        }

        $this->outbound->queueInteractive($ctx->conversation, ['text' => $text, 'buttons' => $buttons], [
            'sent_by_ai' => true,
            'flow_id' => $ctx->run->flow_id,
            'flow_run_id' => $ctx->run->id,
            'node_id' => $node->id,
        ]);

        $sent[] = $node->id;
        $context = $ctx->run->context ?? [];
        $context['_sent'] = $sent;
        $ctx->run->update(['context' => $context]);

        // Dừng chờ postback (buyer bấm nút) — resume theo handle nút.
        return NodeResult::wait();
    }
}
