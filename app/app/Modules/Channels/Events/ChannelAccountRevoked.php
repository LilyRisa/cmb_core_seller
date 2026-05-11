<?php

namespace CMBcoreSeller\Modules\Channels\Events;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired when a shop is disconnected by the user or deauthorized by the seller. */
class ChannelAccountRevoked
{
    use Dispatchable;

    public function __construct(public readonly ChannelAccount $account, public readonly string $reason) {}
}
