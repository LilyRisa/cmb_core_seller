<?php

namespace CMBcoreSeller\Modules\Accounting\Events;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PeriodClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly FiscalPeriod $period) {}
}
