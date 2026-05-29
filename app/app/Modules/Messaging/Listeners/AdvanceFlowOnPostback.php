<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\PostbackReceived;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * PostbackReceived → giải mã payload {node_id, handle}; nếu hội thoại có run đang
 * `waiting` ĐÚNG node đã gửi bộ nút ⇒ resume theo edge `handle`. Payload không phải
 * của flow (get_started/menu) ⇒ bỏ qua. Chạy ngoài auth tenant ⇒ tenant từ hội thoại.
 */
class AdvanceFlowOnPostback implements ShouldQueue
{
    public string $queue = 'messaging';

    public function __construct(private FlowEngine $engine) {}

    public function handle(PostbackReceived $event): void
    {
        $decoded = FlowPostbackPayload::decode($event->payload);
        if ($decoded === null) {
            return;
        }

        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv) {
            return;
        }

        $run = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('conversation_id', $conv->id)
            ->where('status', FlowRun::STATUS_WAITING)
            ->first();

        // Stale guard: chỉ resume nếu đang chờ đúng node đã gửi bộ nút này (tránh
        // bấm nút cũ khi luồng đã đi tiếp / đổi node).
        if (! $run || (string) $run->current_node_id !== $decoded['node_id']) {
            return;
        }

        $this->engine->resume($run, $conv, null, $decoded['handle']);
    }
}
