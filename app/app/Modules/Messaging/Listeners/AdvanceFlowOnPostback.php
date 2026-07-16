<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Events\PostbackReceived;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * PostbackReceived → giải mã payload {flow_id, node_id, handle}; nếu hội thoại có run
 * đang `waiting` ĐÚNG node đã gửi bộ nút ⇒ resume theo edge `handle`. Payload không phải
 * của flow (get_started/menu) ⇒ bỏ qua. Chạy ngoài auth tenant ⇒ tenant từ hội thoại.
 *
 * Không còn run `waiting` nào khớp (run đã ended/failed vì lý do khác — race, admin sửa
 * flow, v.v. — không phải lỗi cụ thể đã vá ở FlowEngine::resume): payload TỰ mang
 * `flow_id` nên vẫn đủ dữ kiện để định tuyến lại (revive) thay vì rớt âm thầm như trước
 * (khách bấm nút xong không có phản hồi gì, xem sự cố tenant Enko Store 2026-07-16).
 * Payload cũ (gửi trước khi thêm field flow_id) không có field này ⇒ giữ hành vi cũ.
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

        // Stale guard: có run đang chờ nhưng KHÁC node đã gửi bộ nút này (luồng đã đi
        // tiếp / đổi node) ⇒ nút cũ, bỏ qua — không revive (không đè lên tiến trình
        // hiện tại của run đang chạy thật).
        if ($run) {
            if ((string) $run->current_node_id === $decoded['node_id']) {
                $this->engine->resume($run, $conv, null, $decoded['handle']);
            }

            return;
        }

        if ($decoded['flow_id'] === null) {
            return;
        }

        $flow = AutomationFlow::withoutGlobalScope(TenantScope::class)
            ->where('id', $decoded['flow_id'])
            ->where('tenant_id', $conv->tenant_id)
            ->where('status', AutomationFlow::STATUS_ACTIVE)
            ->where('enabled', true)
            ->first();
        if (! $flow) {
            return;
        }

        $this->engine->revive($flow, $conv, $decoded['node_id'], $decoded['handle']);
    }
}
