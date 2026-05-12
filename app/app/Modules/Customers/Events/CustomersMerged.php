<?php

namespace CMBcoreSeller\Modules\Customers\Events;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;

class CustomersMerged
{
    use Dispatchable;

    public function __construct(public readonly Customer $kept, public readonly Customer $removed, public readonly ?User $by) {}
}
