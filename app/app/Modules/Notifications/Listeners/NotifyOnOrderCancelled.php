<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SPEC 0036 — đơn chuyển sang trạng thái HỦY ⇒ thông báo in-app. Chỉ fire đúng lần
 * chuyển sang `Cancelled` (event chỉ phát khi status đổi), dedup theo order id.
 */
class NotifyOnOrderCancelled implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to !== StandardOrderStatus::Cancelled) {
            return;
        }

        $order = $event->order;
        $label = $order->order_number ?: ($order->external_order_id ?: ('#'.$order->getKey()));

        $this->dispatcher->dispatch((int) $order->tenant_id, [
            'type' => NotificationType::ORDER_CANCELLED,
            'level' => 'info',
            'title' => "Đơn {$label} đã hủy",
            'body' => 'Một đơn hàng vừa chuyển sang trạng thái đã hủy.',
            'action_url' => '/orders/'.$order->getKey(),
            'data' => [
                'order_id' => (int) $order->getKey(),
                'order_number' => $order->order_number,
            ],
            'dedup_key' => 'order.cancelled:'.$order->getKey(),
        ]);
    }
}
