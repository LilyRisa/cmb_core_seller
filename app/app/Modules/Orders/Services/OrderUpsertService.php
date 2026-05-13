<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Modules\Orders\Contracts\OrderUpsertContract;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent upsert of a normalized OrderDTO into orders/order_items/order_status_history.
 * Rules: docs/03-domain/order-sync-pipeline.md §4, docs/03-domain/order-status-state-machine.md.
 *
 *  - unique key (source, channel_account_id, external_order_id)
 *  - skip if DTO.sourceUpdatedAt <= existing order.source_updated_at (late/out-of-order)
 *  - map raw_status -> canonical via the connector (passed StandardOrderStatus)
 *  - write an order_status_history row only when the canonical status changes
 *  - upsert order_items by (order_id, external_item_id); set sku_id later (Phase 2 SKU mapping)
 *  - everything in one transaction; domain events fire afterCommit
 *  - channel data is the source of truth; an abnormal backward status jump sets has_issue
 */
class OrderUpsertService implements OrderUpsertContract
{
    public function __construct(private OrderStateMachine $stateMachine) {}

    public function upsert(OrderDTO $dto, int $tenantId, ?int $channelAccountId, string $historySource): Order
    {
        // Connector already mapped the status onto the canonical value via $dto.raw['_std_status']?
        // No — keep the connector out of here: the caller resolves the status and stuffs it in.
        // We accept it via the helper below for clarity.
        return $this->doUpsert($dto, $tenantId, $channelAccountId, $historySource, $this->resolveStatus($dto));
    }

    /** Allow callers that already have the connector to pass the mapped status explicitly. */
    public function upsertWithStatus(OrderDTO $dto, int $tenantId, ?int $channelAccountId, string $historySource, StandardOrderStatus $status): Order
    {
        return $this->doUpsert($dto, $tenantId, $channelAccountId, $historySource, $status);
    }

    /**
     * Apply *only* a status change to an order that already exists — used when a
     * webhook carries the new order status but re-fetching the full detail is
     * unavailable (sandbox / transient API error). Does NOT bump `source_updated_at`,
     * so a later full re-fetch can still enrich the order. Returns null if the
     * order isn't in our DB yet (caller must re-fetch the detail to create it).
     * See docs/03-domain/order-sync-pipeline.md §2, docs/03-domain/order-status-state-machine.md.
     */
    public function applyStatusFromWebhook(int $tenantId, ?int $channelAccountId, string $source, string $externalOrderId, StandardOrderStatus $status, string $rawStatus, string $historySource = 'webhook'): ?Order
    {
        [$order, $changed, $from] = DB::transaction(function () use ($tenantId, $channelAccountId, $source, $externalOrderId, $status, $rawStatus, $historySource) {
            /** @var Order|null $order */
            // `withTrashed()` để webhook status-only cũng restore được row đã soft-delete (xem doUpsert).
            $order = Order::withoutGlobalScope(TenantScope::class)
                ->withTrashed()
                ->where('source', $source)->where('channel_account_id', $channelAccountId)->where('external_order_id', $externalOrderId)
                ->lockForUpdate()->first();
            if ($order === null) {
                return [null, false, null];
            }
            if ($order->trashed()) {
                $order->restore();
            }
            $previous = $order->status;
            if ($previous === $status) {
                $order->forceFill(['raw_status' => $rawStatus, 'last_synced_at' => now()])->save();

                return [$order, false, $previous];
            }

            $fill = ['status' => $status, 'raw_status' => $rawStatus, 'last_synced_at' => now()];
            // Stamp the lifecycle timestamp the first time we see this status (the full re-fetch refines it).
            $stamp = match ($status) {
                StandardOrderStatus::Shipped => 'shipped_at',
                StandardOrderStatus::Delivered => 'delivered_at',
                StandardOrderStatus::Completed => 'completed_at',
                StandardOrderStatus::Cancelled => 'cancelled_at',
                default => null,
            };
            if ($stamp !== null && $order->getAttribute($stamp) === null) {
                $fill[$stamp] = now();
            }
            if ($this->stateMachine->isAbnormalBackwardJump($previous, $status)) {
                $fill['has_issue'] = true;
                $fill['issue_reason'] = "Sàn báo lùi trạng thái bất thường: {$previous->value} → {$status->value}";
            }
            $order->forceFill($fill)->save();

            OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenantId, 'order_id' => $order->getKey(),
                'from_status' => $previous->value, 'to_status' => $status->value, 'raw_status' => $rawStatus,
                'source' => $historySource, 'changed_at' => now(), 'payload' => ['raw_status' => $rawStatus, 'via' => 'webhook_payload'], 'created_at' => now(),
            ]);

            return [$order, true, $previous];
        });

        if ($order === null) {
            return null;
        }
        OrderUpserted::dispatch($order, false);
        if ($changed) {
            OrderStatusChanged::dispatch($order, $from, $status, $historySource);
        }

        return $order;
    }

    private function doUpsert(OrderDTO $dto, int $tenantId, ?int $channelAccountId, string $historySource, StandardOrderStatus $status): Order
    {
        [$order, $created, $statusChanged, $from] = DB::transaction(function () use ($dto, $tenantId, $channelAccountId, $historySource, $status) {
            // CHỐT: phải `withTrashed()` ⇒ bắt cả row đã soft-delete. DB unique index
            // `orders_source_account_external_unique` (source, channel_account_id, external_order_id) KHÔNG có
            // partial predicate `WHERE deleted_at IS NULL` ⇒ row đã soft-delete vẫn chiếm key. Nếu chỉ query
            // active rồi INSERT lại sẽ throw `SQLSTATE[23505]` → sync crash cả page. Sàn re-push đơn (vd sau
            // khi user xoá kết nối rồi reconnect, hoặc xoá đơn nhầm) ⇒ restore + update lại, không tạo mới.
            // Xem `bba66d3` (xoá kết nối ⇒ soft-delete đơn) và docs/03-domain/order-sync-pipeline.md §4.1.
            /** @var Order|null $order */
            $order = Order::withoutGlobalScope(TenantScope::class)
                ->withTrashed()
                ->where('source', $dto->source)
                ->where('channel_account_id', $channelAccountId)
                ->where('external_order_id', $dto->externalOrderId)
                ->lockForUpdate()
                ->first();

            $restored = false;
            if ($order && $order->trashed()) {
                $order->restore();   // sàn đẩy lại đơn ⇒ đem row cũ trở về, không insert mới (sẽ collision)
                $restored = true;
            }

            $created = $order === null;

            // Out-of-order / late: do nothing if we already have a newer (or equal) snapshot.
            if (! $created && $order->source_updated_at && $dto->sourceUpdatedAt->lessThanOrEqualTo($order->source_updated_at)) {
                return [$order, false, false, $order->status];
            }

            $previousStatus = $created ? null : $order->status;
            $hasIssue = $created ? false : (bool) $order->has_issue;
            $issueReason = $created ? null : $order->issue_reason;
            if (! $created && $previousStatus !== $status && $this->stateMachine->isAbnormalBackwardJump($previousStatus, $status)) {
                $hasIssue = true;
                $issueReason = "Sàn báo lùi trạng thái bất thường: {$previousStatus->value} → {$status->value}";
            }

            $attrs = [
                'tenant_id' => $tenantId,
                'source' => $dto->source,
                'channel_account_id' => $channelAccountId,
                'external_order_id' => $dto->externalOrderId,
                'order_number' => $dto->orderNumber ?: $dto->externalOrderId,
                'status' => $status,
                'raw_status' => $dto->rawStatus,
                'payment_status' => $dto->paymentStatus,
                'buyer_name' => $dto->buyer['name'] ?? null,
                'buyer_phone' => $dto->buyer['phone'] ?? ($dto->shippingAddress['phone'] ?? null),
                'shipping_address' => $dto->shippingAddress ?: null,
                'currency' => $dto->currency ?: 'VND',
                'item_total' => $dto->itemTotal,
                'shipping_fee' => $dto->shippingFee,
                'platform_discount' => $dto->platformDiscount,
                'seller_discount' => $dto->sellerDiscount,
                'tax' => $dto->tax,
                'cod_amount' => $dto->codAmount,
                'grand_total' => $dto->grandTotal,
                'is_cod' => $dto->isCod,
                'fulfillment_type' => $dto->fulfillmentType ?? null,
                'carrier' => $this->primaryCarrier($dto->packages),
                'placed_at' => $dto->placedAt,
                'paid_at' => $dto->paidAt,
                'shipped_at' => $dto->shippedAt,
                'delivered_at' => $dto->deliveredAt,
                'completed_at' => $dto->completedAt,
                'cancelled_at' => $dto->cancelledAt,
                'cancel_reason' => $dto->cancelReason,
                'packages' => $dto->packages ?: null,
                'raw_payload' => $dto->raw ?: null,
                'source_updated_at' => $dto->sourceUpdatedAt,
                'last_synced_at' => now(),
                'has_issue' => $hasIssue,
                'issue_reason' => $issueReason,
            ];

            if ($created) {
                $order = new Order($attrs);
                $order->forceFill(['tags' => []]);
                $order->save();
            } else {
                $order->forceFill($attrs)->save();
            }

            // Items: upsert by (order_id, external_item_id); remove items no longer present.
            $keep = [];
            foreach ($dto->items as $item) {
                $keep[] = $item->externalItemId;
                OrderItem::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                    ['order_id' => $order->getKey(), 'external_item_id' => $item->externalItemId],
                    [
                        'tenant_id' => $tenantId,
                        'external_product_id' => $item->externalProductId,
                        'external_sku_id' => $item->externalSkuId,
                        'seller_sku' => $item->sellerSku,
                        'name' => $item->name,
                        'variation' => $item->variation,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unitPrice,
                        'discount' => $item->discount,
                        'subtotal' => $item->unitPrice * $item->quantity - $item->discount,
                        'image' => $item->image,
                        'raw' => $item->raw ?: null,
                        // sku_id stays null until SKU mapping (Phase 2).
                    ],
                );
            }
            if ($keep !== []) {
                OrderItem::withoutGlobalScope(TenantScope::class)
                    ->where('order_id', $order->getKey())->whereNotIn('external_item_id', $keep)->delete();
            }

            $statusChanged = $created || $previousStatus !== $status;
            if ($statusChanged) {
                OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => $tenantId,
                    'order_id' => $order->getKey(),
                    'from_status' => $previousStatus?->value,
                    'to_status' => $status->value,
                    'raw_status' => $dto->rawStatus,
                    'source' => $historySource,
                    'changed_at' => $dto->sourceUpdatedAt,
                    'payload' => ['raw_status' => $dto->rawStatus],
                    'created_at' => now(),
                ]);
            }

            // Keep wasRecentlyCreated intact for the caller (no refresh()).
            return [$order, $created, $statusChanged, $previousStatus];
        });

        OrderUpserted::dispatch($order, $created);
        if ($statusChanged) {
            OrderStatusChanged::dispatch($order, $from, $status, $historySource);
        }

        return $order;
    }

    private function resolveStatus(OrderDTO $dto): StandardOrderStatus
    {
        // When called via the bare contract (no connector), fall back to the connector
        // registry to map raw_status. Callers in the sync pipeline use upsertWithStatus().
        $registry = app(ChannelRegistry::class);
        if ($registry->has($dto->source)) {
            return $registry->for($dto->source)->mapStatus($dto->rawStatus, $dto->raw);
        }

        return StandardOrderStatus::tryFrom($dto->rawStatus) ?? StandardOrderStatus::Pending;
    }

    /**
     * Denormalized "primary carrier" for the orders list filter — the first package's carrier.
     *
     * @param  array<int,mixed>|null  $packages
     */
    private function primaryCarrier(?array $packages): ?string
    {
        foreach ($packages ?? [] as $p) {
            $c = is_array($p) ? trim((string) ($p['carrier'] ?? '')) : '';
            if ($c !== '') {
                return $c;
            }
        }

        return null;
    }
}
