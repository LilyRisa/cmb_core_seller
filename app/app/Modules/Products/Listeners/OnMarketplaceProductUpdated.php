<?php

namespace CMBcoreSeller\Modules\Products\Listeners;

use CMBcoreSeller\Modules\Channels\Events\MarketplaceProductUpdated;
use CMBcoreSeller\Modules\Products\Jobs\RefreshListingQcStatus;

/** Webhook product_update của shop → re-check trạng thái QC các nháp đang chờ duyệt. */
class OnMarketplaceProductUpdated
{
    public function handle(MarketplaceProductUpdated $event): void
    {
        RefreshListingQcStatus::dispatch((int) $event->channelAccount->getKey());
    }
}
