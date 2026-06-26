<?php

namespace CMBcoreSeller\Modules\Customers\Listeners;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/**
 * Hoàn ví trả trước khi đơn bị huỷ/hoàn (idempotent). Đơn không trả bằng ví ⇒ no-op (refundForOrder trả null).
 * SPEC 2026-06-26.
 */
class RefundWalletOnOrderCancelled
{
    public function __construct(private readonly CustomerWallet $wallet) {}

    public function handle(OrderStatusChanged $event): void
    {
        if (! in_array($event->to, [StandardOrderStatus::Cancelled, StandardOrderStatus::ReturnedRefunded], true)) {
            return;
        }
        $order = $event->order;
        if (! $order->customer_id) {
            return;
        }
        $this->wallet->refundForOrder((int) $order->tenant_id, (int) $order->customer_id, (int) $order->getKey(), null);
    }
}
