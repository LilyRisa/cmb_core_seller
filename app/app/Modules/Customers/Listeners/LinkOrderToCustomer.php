<?php

namespace CMBcoreSeller\Modules\Customers\Listeners;

use CMBcoreSeller\Modules\Customers\Services\CustomerLinkingService;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * On every OrderUpserted (afterCommit), match the order to a customer and refresh
 * stats/reputation/auto-notes. Idempotent (recompute reads straight from `orders`).
 * queue: customers (lower priority than the order pipeline). OrderUpsertService
 * fires OrderUpserted *after* its own transaction commits, so the order row is
 * already durable. The race between two orders of the same buyer is handled by the
 * lockForUpdate + unique (tenant_id, phone_hash) inside the service. See SPEC 0002 §4.2, §6.3.
 */
class LinkOrderToCustomer implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'customers';

    public int $tries = 3;

    public function handle(OrderUpserted $event): void
    {
        app(CustomerLinkingService::class)->linkOrder($event->order);
    }
}
