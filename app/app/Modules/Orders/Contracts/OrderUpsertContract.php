<?php

namespace CMBcoreSeller\Modules\Orders\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Modules\Orders\Models\Order;

/**
 * The seam other modules use to push a normalized order into the Orders module
 * — the Channels sync jobs (ProcessWebhookEvent / SyncOrdersForShop) call this,
 * so Channels never touches Orders' internals. Bound to OrderUpsertService.
 * Idempotent: see docs/03-domain/order-sync-pipeline.md §4.
 */
interface OrderUpsertContract
{
    /**
     * Upsert one order from a connector's normalized DTO.
     *
     * @param  int  $tenantId  the tenant that owns the channel account
     * @param  string  $historySource  one of OrderStatusHistory::SOURCE_* — where this update came from
     */
    public function upsert(OrderDTO $dto, int $tenantId, ?int $channelAccountId, string $historySource): Order;
}
