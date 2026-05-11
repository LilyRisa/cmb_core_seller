<?php

namespace CMBcoreSeller\Modules\Orders\Events;

use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired (afterCommit) when an order's canonical status changes. */
class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly ?StandardOrderStatus $from,
        public readonly StandardOrderStatus $to,
        public readonly string $source,
    ) {}
}
