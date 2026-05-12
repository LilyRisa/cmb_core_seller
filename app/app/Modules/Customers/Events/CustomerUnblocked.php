<?php

namespace CMBcoreSeller\Modules\Customers\Events;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerUnblocked
{
    use Dispatchable;

    public function __construct(public readonly Customer $customer, public readonly ?User $by) {}
}
