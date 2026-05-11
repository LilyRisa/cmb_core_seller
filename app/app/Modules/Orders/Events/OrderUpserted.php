<?php

namespace CMBcoreSeller\Modules\Orders\Events;

use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired (afterCommit) whenever an order is created or updated via OrderUpsertService. */
class OrderUpserted
{
    use Dispatchable;

    public function __construct(public readonly Order $order, public readonly bool $created) {}
}
