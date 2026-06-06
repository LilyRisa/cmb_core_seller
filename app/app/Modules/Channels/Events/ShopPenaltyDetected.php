<?php

namespace CMBcoreSeller\Modules\Channels\Events;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\ShopPenaltyEvent;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát khi nhận sự kiện điểm phạt/vi phạm từ webhook sàn. Listener (vd thông báo cho seller)
 * lắng nghe để cảnh báo real-time. SPEC 2026-06-06.
 */
class ShopPenaltyDetected
{
    use Dispatchable;

    public function __construct(
        public readonly ShopPenaltyEvent $event,
        public readonly ChannelAccount $account,
    ) {}
}
