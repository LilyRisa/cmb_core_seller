<?php

namespace CMBcoreSeller\Modules\Channels\Events;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired when a token refresh fails — sync for this shop stops; the user must re-connect. */
class ChannelAccountNeedsReconnect
{
    use Dispatchable;

    public function __construct(public readonly ChannelAccount $account, public readonly string $reason) {}
}
