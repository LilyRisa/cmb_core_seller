<?php

namespace CMBcoreSeller\Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Fired sau khi transaction đăng ký trải nghiệm Pro commit thành công (không fire bên
 * trong transaction — tránh gửi email cho một grant có thể bị rollback).
 * Notifications module nghe event này để gửi mail kích hoạt tới chủ shop.
 */
class ProTrialActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly Carbon $grantedAt,
        public readonly Carbon $expiresAt,
    ) {}
}
