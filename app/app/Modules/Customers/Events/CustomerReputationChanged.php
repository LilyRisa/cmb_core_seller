<?php

namespace CMBcoreSeller\Modules\Customers\Events;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired only when the reputation *label* changes (not on every score tick). */
class CustomerReputationChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Customer $customer,
        public readonly string $fromLabel,
        public readonly string $toLabel,
        public readonly int $fromScore,
        public readonly int $toScore,
    ) {}
}
