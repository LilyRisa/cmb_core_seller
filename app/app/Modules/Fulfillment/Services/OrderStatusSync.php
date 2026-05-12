<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/**
 * Helper for the Fulfillment module to move an order to a new canonical status as a
 * side effect of a shipment event: writes an order_status_history row and re-fires
 * OrderUpserted so Inventory (reserve/ship/release) and Customers re-apply. Idempotent —
 * no-op when the order is already at the target status. See SPEC 0006 §3.
 */
class OrderStatusSync
{
    /** @param StandardOrderStatus[] $onlyFrom apply only if the current status is one of these (empty = always) */
    public function apply(Order $order, StandardOrderStatus $to, string $source, array $onlyFrom = [], ?int $userId = null): bool
    {
        $from = $order->status;
        if ($from === $to) {
            return false;
        }
        if ($onlyFrom !== [] && ! in_array($from, $onlyFrom, true)) {
            // status moved past our scope (e.g. channel already marked it shipped) — still re-fire so stock catches up.
            OrderUpserted::dispatch($order, false);

            return false;
        }

        $now = now();
        $attrs = ['status' => $to, 'raw_status' => $to->value];
        if ($to === StandardOrderStatus::Shipped && ! $order->shipped_at) {
            $attrs['shipped_at'] = $now;
        }
        if ($to === StandardOrderStatus::Delivered && ! $order->delivered_at) {
            $attrs['delivered_at'] = $now;
        }
        $order->forceFill($attrs)->save();

        OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $order->tenant_id, 'order_id' => $order->getKey(),
            'from_status' => $from->value, 'to_status' => $to->value, 'raw_status' => $to->value,
            'source' => $source, 'changed_at' => $now,
            'payload' => $userId ? ['by' => $userId] : null, 'created_at' => $now,
        ]);

        OrderUpserted::dispatch($order, false);

        return true;
    }
}
