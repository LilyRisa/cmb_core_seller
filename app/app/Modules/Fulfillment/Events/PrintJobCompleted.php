<?php

namespace CMBcoreSeller\Modules\Fulfillment\Events;

use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired when a print_job finishes rendering (done or error). */
class PrintJobCompleted
{
    use Dispatchable;

    public function __construct(public readonly PrintJob $printJob) {}
}
