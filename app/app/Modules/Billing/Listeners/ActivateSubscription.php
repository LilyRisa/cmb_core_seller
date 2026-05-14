<?php

namespace CMBcoreSeller\Modules\Billing\Listeners;

use CMBcoreSeller\Modules\Billing\Events\InvoicePaid;
use CMBcoreSeller\Modules\Billing\Services\ActivateSubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Khi `InvoicePaid` ⇒ kích hoạt subscription mới (swap khỏi gói cũ).
 *
 * Queue `billing` priority thấp — vài giây trễ là OK (UX poll vẫn thấy).
 */
class ActivateSubscription implements ShouldQueue
{
    public string $queue = 'billing';

    public int $tries = 3;

    public function __construct(protected ActivateSubscriptionService $service) {}

    public function handle(InvoicePaid $event): void
    {
        $this->service->activate($event->invoice);
    }
}
