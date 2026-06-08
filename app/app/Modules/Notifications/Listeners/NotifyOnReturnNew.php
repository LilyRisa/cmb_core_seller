<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Orders\Events\ReturnStatusChanged;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SPEC 0036 — có yêu cầu hủy/hoàn MỚI (after-sales chuyển sang `Requested`) ⇒ thông báo
 * in-app. Dedup theo return id ⇒ 1 thông báo / yêu cầu.
 */
class NotifyOnReturnNew implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(ReturnStatusChanged $event): void
    {
        if ($event->to !== AfterSalesStatus::Requested) {
            return;
        }

        $return = $event->return;
        $label = $return->order?->order_number
            ?: ($return->external_order_id ?: ('#'.$return->getKey()));

        $this->dispatcher->dispatch((int) $return->tenant_id, [
            'type' => NotificationType::ORDER_RETURN_NEW,
            'level' => 'info',
            'title' => "Đơn {$label} có yêu cầu hủy/hoàn mới",
            'body' => 'Một yêu cầu hủy/hoàn vừa được tạo — vui lòng xem xét xử lý.',
            'action_url' => $return->order_id ? '/orders/'.$return->order_id : '/orders',
            'data' => [
                'return_id' => (int) $return->getKey(),
                'order_id' => $return->order_id,
                'kind' => $return->kind,
            ],
            'dedup_key' => 'order.return:'.$return->getKey(),
        ]);
    }
}
