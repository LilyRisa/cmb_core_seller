<?php

namespace CMBcoreSeller\Modules\Billing\Listeners;

use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Khi tenant mới tạo ⇒ khởi động trial 14 ngày.
 *
 * Idempotent — nếu tenant đã có alive subscription thì service no-op (tránh trùng nếu
 * event được dispatch 2 lần do retry).
 *
 * Queue `billing` (priority thấp — đăng ký xong user thấy banner trial sau vài giây là OK).
 * Trong test (`Bus::fake` / sync queue) listener sẽ chạy ngay.
 */
class StartTrialSubscription implements ShouldQueue
{
    public string $queue = 'billing';

    public int $tries = 3;

    public function __construct(protected SubscriptionService $service) {}

    public function handle(TenantCreated $event): void
    {
        $this->service->startTrial((int) $event->tenant->getKey());
    }
}
