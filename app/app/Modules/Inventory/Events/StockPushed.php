<?php

namespace CMBcoreSeller\Modules\Inventory\Events;

use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use Illuminate\Foundation\Events\Dispatchable;

class StockPushed
{
    use Dispatchable;

    public function __construct(public readonly ChannelListing $listing, public readonly int $desired, public readonly bool $ok) {}
}
