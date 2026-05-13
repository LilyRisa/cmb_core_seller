<?php

namespace CMBcoreSeller\Modules\Inventory\Services;

use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Support\SkuCodeNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Applies an order's stock effects (reserve / ship / release / return) to the
 * ledger, resolving each order line to its master SKU(s) via sku_mappings or the
 * `seller_sku == sku_code` auto-match. Idempotent (the ledger dedupes per
 * (order_item, sku, type)). Runs from the OrderUpserted listener with the tenant
 * scope off. See SPEC 0003 §3-4, docs/03-domain/inventory-and-sku-mapping.md §3.
 */
class OrderInventoryService
{
    public function __construct(private InventoryLedgerService $ledger, private FifoCostService $fifo) {}

    public function apply(Order $order): void
    {
        $tenantId = (int) $order->tenant_id;
        $userId = null;
        /** @var Collection<int, OrderItem> $items */
        $items = OrderItem::withoutGlobalScope(TenantScope::class)->where('order_id', $order->getKey())->get();

        // Pre-load listings/mappings/skus in 3 queries to avoid N+1 inside resolveComponents.
        $preloaded = $this->preloadForOrder($order, $items, $tenantId);

        $anyUnmapped = false;
        foreach ($items as $item) {
            $components = $this->resolveComponents($order, $item, $tenantId, $preloaded);
            if ($components === []) {
                // A manual-order line with no SKU reference at all is an intentional ad-hoc / "quick product"
                // line (see ManualOrderService) — it just isn't tracked in inventory, it's NOT an unmapped-SKU
                // issue. Channel lines (or manual lines that *should* resolve) still flag has_issue.
                $isAdHocManualLine = $order->source === 'manual' && ! $item->sku_id && ! $item->seller_sku && ! $item->external_sku_id;
                if (! $isAdHocManualLine) {
                    $anyUnmapped = true;
                }

                continue;
            }
            foreach ($components as [$skuId, $qtyPer]) {
                $qty = (int) $qtyPer * (int) $item->quantity;
                if ($qty <= 0) {
                    continue;
                }
                $this->applyForComponent($tenantId, $order, (int) $item->getKey(), (int) $skuId, $qty, $userId);
            }
        }

        $this->reflectUnmappedIssue($order, $anyUnmapped);
    }

    /**
     * Pre-load all ChannelListings, SkuMappings and Skus needed by the items in one batch
     * to avoid N+1 queries inside resolveComponents.
     *
     * @param  Collection<int, OrderItem>  $items
     * @return array{listings:\Illuminate\Support\Collection<int,ChannelListing>, mappings:\Illuminate\Support\Collection, skus:\Illuminate\Support\Collection<int,Sku>}
     */
    private function preloadForOrder(Order $order, Collection $items, int $tenantId): array
    {
        $empty = ['listings' => collect(), 'mappings' => collect(), 'skus' => collect()];
        if (! $order->channel_account_id) {
            return $empty;
        }

        $externalSkuIds = $items->pluck('external_sku_id')->filter()->unique()->values()->all();
        $sellerSkus = $items->pluck('seller_sku')->filter()->unique()->values()->all();

        if (! $externalSkuIds && ! $sellerSkus) {
            return $empty;
        }

        $listings = ChannelListing::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('channel_account_id', $order->channel_account_id)
            ->where(function ($q) use ($externalSkuIds, $sellerSkus) {
                if ($externalSkuIds) {
                    $q->whereIn('external_sku_id', $externalSkuIds);
                }
                if ($sellerSkus) {
                    $q->orWhereIn('seller_sku', $sellerSkus);
                }
            })
            ->get()
            ->keyBy('id');

        $mappings = $listings->isNotEmpty()
            ? SkuMapping::withoutGlobalScope(TenantScope::class)
                ->whereIn('channel_listing_id', $listings->keys())->get()->groupBy('channel_listing_id')
            : collect();

        $skus = $sellerSkus
            ? Sku::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->whereNull('deleted_at')->get(['id', 'sku_code'])
            : collect();

        return ['listings' => $listings, 'mappings' => $mappings, 'skus' => $skus];
    }

    /**
     * @param  array{listings:\Illuminate\Support\Collection,mappings:\Illuminate\Support\Collection,skus:\Illuminate\Support\Collection}  $preloaded
     * @return list<array{0:int,1:int}> list of [skuId, qtyPerUnitOfOrderLine]
     */
    private function resolveComponents(Order $order, OrderItem $item, int $tenantId, array $preloaded = []): array
    {
        // Manual orders (and channel items already resolved to a single SKU): use it directly.
        if ($item->sku_id) {
            return [[(int) $item->sku_id, 1]];
        }

        // Channel order: find the matching listing → its mappings (single or bundle).
        if ($order->channel_account_id && ($item->external_sku_id || $item->seller_sku)) {
            /** @var ChannelListing|null $listing */
            $listing = $preloaded['listings']->first(function ($l) use ($item) {
                return ($item->external_sku_id && $l->external_sku_id === $item->external_sku_id)
                    || ($item->seller_sku && $l->seller_sku === $item->seller_sku);
            });
            if ($listing) {
                $mappings = $preloaded['mappings']->get($listing->getKey(), collect());
                if ($mappings->isNotEmpty()) {
                    if ($mappings->count() === 1 && $mappings->first()->type === SkuMapping::TYPE_SINGLE) {
                        // store the single SKU on the order line for convenience
                        $this->persistSkuId($item, (int) $mappings->first()->sku_id);
                    }

                    return $mappings->map(fn (SkuMapping $m) => [(int) $m->sku_id, max(1, (int) $m->quantity)])->all();
                }
            }
        }

        // Auto-match: seller_sku == sku_code (normalized).
        $code = SkuCodeNormalizer::normalize($item->seller_sku);
        if ($code !== '') {
            $sku = $preloaded['skus']->first(fn (Sku $s) => SkuCodeNormalizer::normalize($s->sku_code) === $code);
            if ($sku) {
                $this->persistSkuId($item, (int) $sku->getKey());

                return [[(int) $sku->getKey(), 1]];
            }
        }

        return [];
    }

    private function persistSkuId(OrderItem $item, int $skuId): void
    {
        if ((int) $item->sku_id !== $skuId) {
            $item->forceFill(['sku_id' => $skuId])->save();
        }
    }

    private function applyForComponent(int $tenantId, Order $order, int $orderItemId, int $skuId, int $qty, ?int $userId): void
    {
        $refType = 'order_item';
        $status = $order->status;
        $shipped = $this->ledger->movementExists($tenantId, $skuId, $refType, $orderItemId, 'order_ship');
        $reserved = $this->ledger->movementExists($tenantId, $skuId, $refType, $orderItemId, 'order_reserve');

        try {
            if ($status->isPreShipment()) {
                if (! $shipped) {
                    $this->ledger->reserve($tenantId, $skuId, $qty, $refType, $orderItemId, null, $userId);
                }

                return;
            }

            if (in_array($status, [StandardOrderStatus::Shipped, StandardOrderStatus::Delivered, StandardOrderStatus::Completed, StandardOrderStatus::DeliveryFailed], true)) {
                $hadOpen = $this->ledger->hasOpenReservation($tenantId, $skuId, $refType, $orderItemId);
                $this->ledger->ship($tenantId, $skuId, $qty, $refType, $orderItemId, $hadOpen, null, $userId);
                // FIFO COGS — bất biến 1 row / order_item; ship lại ⇒ no-op. SPEC 0014.
                $this->fifo->consumeForShip($tenantId, (int) $order->getKey(), $orderItemId, $skuId, $qty, null, null, $this->costMethodFor($tenantId));

                return;
            }

            // Returning = khách đang yêu cầu trả hàng nhưng chưa giao thành công.
            // Nếu chưa ship → release reservation; nếu đã ship → returnIn (hàng về kho).
            if ($status === StandardOrderStatus::Returning) {
                if ($shipped) {
                    $this->ledger->returnIn($tenantId, $skuId, $qty, $refType, $orderItemId, null, $userId);
                    $this->fifo->unconsume($tenantId, $orderItemId);
                } elseif ($reserved) {
                    $this->ledger->release($tenantId, $skuId, $qty, $refType, $orderItemId, null, $userId);
                }

                return;
            }

            if ($status === StandardOrderStatus::ReturnedRefunded) {
                if ($shipped) {
                    $this->ledger->returnIn($tenantId, $skuId, $qty, $refType, $orderItemId, null, $userId);
                    $this->fifo->unconsume($tenantId, $orderItemId);
                } elseif ($reserved) {
                    $this->ledger->release($tenantId, $skuId, $qty, $refType, $orderItemId, null, $userId);
                }

                return;
            }

            if ($status === StandardOrderStatus::Cancelled) {
                if ($shipped) {
                    $this->ledger->returnIn($tenantId, $skuId, $qty, $refType, $orderItemId, null, $userId);
                    $this->fifo->unconsume($tenantId, $orderItemId);
                } elseif ($reserved) {
                    $this->ledger->release($tenantId, $skuId, $qty, $refType, $orderItemId, null, $userId);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('inventory.apply_failed', ['order' => $order->getKey(), 'item' => $orderItemId, 'sku' => $skuId, 'status' => $status->value, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Tenant chọn phương pháp giá vốn ở `tenant.settings.cost_method` (`fifo` mặc định | `average`). Lưu vào
     * `order_costs.cost_method` để báo cáo biết source — đổi method không ảnh hưởng đơn cũ. SPEC 0014.
     */
    private function costMethodFor(int $tenantId): string
    {
        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::query()->find($tenantId);
        $m = $tenant ? (string) data_get($tenant->settings, 'cost_method', 'fifo') : 'fifo';

        return in_array($m, ['fifo', 'average'], true) ? $m : 'fifo';
    }

    private function reflectUnmappedIssue(Order $order, bool $anyUnmapped): void
    {
        $issue = 'SKU chưa ghép';
        if ($anyUnmapped && ! $order->has_issue) {
            $order->forceFill(['has_issue' => true, 'issue_reason' => $issue])->save();
        } elseif (! $anyUnmapped && $order->has_issue && $order->issue_reason === $issue) {
            $order->forceFill(['has_issue' => false, 'issue_reason' => null])->save();
        }
    }
}
