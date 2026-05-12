<?php

namespace CMBcoreSeller\Modules\Customers\Listeners;

use CMBcoreSeller\Modules\Channels\Events\ChannelAccountRevoked;
use CMBcoreSeller\Modules\Customers\Jobs\AnonymizeCustomersForShop;

/**
 * Shop disconnected → schedule anonymization of its single-shop customers after a
 * retention window (config `customers.anonymize_after_days`, default 90 — long
 * enough for disputes / reconciliation). See SPEC 0002 §8.
 */
class OnChannelAccountRevoked
{
    public function handle(ChannelAccountRevoked $event): void
    {
        $days = (int) config('customers.anonymize_after_days', 90);
        AnonymizeCustomersForShop::dispatch((int) $event->account->tenant_id, (int) $event->account->getKey())
            ->delay(now()->addDays(max(0, $days)));
    }
}
