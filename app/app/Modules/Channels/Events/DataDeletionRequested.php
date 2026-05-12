<?php

namespace CMBcoreSeller\Modules\Channels\Events;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The marketplace asked us to delete a shop's buyer data (`data_deletion` webhook).
 * Modules that hold buyer PII (Customers) listen and anonymize. See SPEC 0002 §8,
 * docs/08-security-and-privacy.md §6.
 */
class DataDeletionRequested
{
    use Dispatchable;

    public function __construct(public readonly ChannelAccount $channelAccount) {}
}
