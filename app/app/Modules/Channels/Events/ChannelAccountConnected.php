<?php

namespace CMBcoreSeller\Modules\Channels\Events;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Foundation\Events\Dispatchable;

class ChannelAccountConnected
{
    use Dispatchable;

    public function __construct(public readonly ChannelAccount $account, public readonly bool $created) {}
}
