<?php

namespace CMBcoreSeller\Modules\Orders\Events;

use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired (afterCommit) when an after-sales record's canonical status changes. */
class ReturnStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly OrderReturn $return,
        public readonly ?AfterSalesStatus $from,
        public readonly AfterSalesStatus $to,
        public readonly string $source,
    ) {}
}
