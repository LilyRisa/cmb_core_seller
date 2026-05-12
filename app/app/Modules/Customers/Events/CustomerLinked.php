<?php

namespace CMBcoreSeller\Modules\Customers\Events;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired after an order has been matched/linked to a customer. See SPEC 0002 §5.3. */
class CustomerLinked
{
    use Dispatchable;

    public function __construct(public readonly Customer $customer, public readonly Order $order, public readonly bool $created) {}
}
