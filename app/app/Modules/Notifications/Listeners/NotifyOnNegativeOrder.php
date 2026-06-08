<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SPEC 0036 — đơn có tổng tiền âm (`grand_total < 0`) ⇒ thông báo in-app. OrderUpserted
 * fire mỗi lần upsert nên dedup theo order id (chỉ 1 thông báo chưa đọc / đơn).
 */
class NotifyOnNegativeOrder implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(OrderUpserted $event): void
    {
        $order = $event->order;
        if ((int) $order->grand_total >= 0) {
            return;
        }

        $label = $order->order_number ?: ($order->external_order_id ?: ('#'.$order->getKey()));

        $this->dispatcher->dispatch((int) $order->tenant_id, [
            'type' => NotificationType::ORDER_NEGATIVE_TOTAL,
            'level' => 'warning',
            'title' => "Đơn {$label} có tổng tiền âm",
            'body' => 'Tổng tiền đơn là '.number_format((int) $order->grand_total).'đ — vui lòng kiểm tra.',
            'action_url' => '/orders/'.$order->getKey(),
            'data' => [
                'order_id' => (int) $order->getKey(),
                'order_number' => $order->order_number,
                'grand_total' => (int) $order->grand_total,
            ],
            'dedup_key' => 'order.negative:'.$order->getKey(),
        ]);
    }
}
