<?php

namespace CMBcoreSeller\Modules\Customers\Listeners;

use CMBcoreSeller\Modules\Channels\Events\DataDeletionRequested;
use CMBcoreSeller\Modules\Customers\Jobs\AnonymizeCustomersForShop;

/** Marketplace data-deletion request → anonymize that shop's buyer PII promptly. */
class OnDataDeletionRequested
{
    public function handle(DataDeletionRequested $event): void
    {
        AnonymizeCustomersForShop::dispatch((int) $event->channelAccount->tenant_id, (int) $event->channelAccount->getKey());
    }
}
