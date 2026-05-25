<?php

namespace CMBcoreSeller\Modules\Orders\Events;

use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired (afterCommit) whenever an after-sales record is created or updated via ReturnUpsertService. */
class ReturnUpserted
{
    use Dispatchable;

    public function __construct(public readonly OrderReturn $return, public readonly bool $created) {}
}
