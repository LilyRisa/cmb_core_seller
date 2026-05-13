<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Collection;

/**
 * Estimated order profit after marketplace fees (SPEC 0012):
 *   estimated_profit ≈ grand_total − platform_fee − shipping_fee_seller − COGS
 *   platform_fee = grand_total × (tenant.settings.platform_fee_pct[source] %)
 *   COGS = Σ over items (qty × SKU.effectiveCost) — effectiveCost theo `cost_method` (bình quân | lô gần nhất)
 * `cost_complete` = mọi dòng đều có sku_id & giá vốn > 0 (false ⇒ COGS thiếu ⇒ lợi nhuận là ước tính trên).
 * Best-effort: phí ship thực / phí thanh toán / hoa hồng theo danh mục → đối soát thật (Phase 6 Finance).
 */
class OrderProfitService
{
    /** @param array<string,mixed>|null $tenantSettings @return array<string,float> source => % */
    public function platformFeePct(?array $tenantSettings): array
    {
        $out = [];
        foreach ((array) (($tenantSettings ?? [])['platform_fee_pct'] ?? []) as $k => $v) {
            $out[(string) $k] = max(0.0, (float) $v);
        }

        return $out;
    }

    /**
     * Annotate a page of orders (without loading their `items` relation) — one batched query for items,
     * one for SKU costs. Stashes the result as the `_profit` attribute (read by OrderResource).
     *
     * @param  Collection<int, Order>  $orders
     */
    public function annotateFromBatch(Collection $orders, ?array $tenantSettings): void
    {
        if ($orders->isEmpty()) {
            return;
        }
        $pct = $this->platformFeePct($tenantSettings);
        $orderIds = $orders->pluck('id')->all();
        $itemsByOrder = OrderItem::query()->whereIn('order_id', $orderIds)->get(['order_id', 'sku_id', 'quantity'])->groupBy('order_id');
        $costs = $this->fetchCosts($itemsByOrder->flatten(1)->pluck('sku_id'));
        $actualByOrder = $this->fetchActualCogs($orderIds);   // FIFO COGS thực — đơn đã ship (SPEC 0014)
        $orders->each(fn (Order $o) => $o->setAttribute('_profit', $this->compute(
            $o, $itemsByOrder->get($o->getKey(), collect()), $pct, $costs, $actualByOrder[$o->getKey()] ?? null,
        )));
    }

    /** Annotate a single order whose `items` relation is already loaded. */
    public function annotateLoaded(Order $order, ?array $tenantSettings): void
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        $actual = $this->fetchActualCogs([$order->getKey()])[$order->getKey()] ?? null;
        $order->setAttribute('_profit', $this->compute(
            $order, $items, $this->platformFeePct($tenantSettings), $this->fetchCosts($items->pluck('sku_id')), $actual,
        ));
    }

    /**
     * Tổng COGS thực từ `order_costs` — bất biến, ghi tại thời điểm ship. Khi có dữ liệu này thì lợi nhuận là
     * **THỰC** (không phải ước tính), `cost_complete=true` và `cost_source='fifo|average|latest'`.
     *
     * @param  list<int>  $orderIds
     * @return array<int, array{cogs:int, source:string}>
     */
    private function fetchActualCogs(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }
        $rows = OrderCost::withoutGlobalScope(TenantScope::class)
            ->whereIn('order_id', $orderIds)
            ->selectRaw('order_id, SUM(cogs_total) AS cogs, MAX(cost_method) AS method, COUNT(*) AS n')
            ->groupBy('order_id')->get();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->order_id] = ['cogs' => (int) $r->cogs, 'source' => (string) ($r->method ?: 'fifo')];
        }

        return $out;
    }

    /** @param Collection<int, mixed> $skuIds @return Collection<int, Sku> keyed by id */
    private function fetchCosts(Collection $skuIds): Collection
    {
        $ids = $skuIds->filter()->unique()->values();

        return $ids->isEmpty() ? collect()
            : Sku::withoutGlobalScope(TenantScope::class)->whereIn('id', $ids->all())->get(['id', 'cost_price', 'cost_method', 'last_receipt_cost'])->keyBy('id');
    }

    /**
     * @param  Collection<int, OrderItem>  $items  rows with at least sku_id + quantity
     * @param  array<string,float>  $platformFeePct
     * @param  Collection<int, Sku>  $skuCosts
     * @param  array{cogs:int,source:string}|null  $actual  COGS thực từ `order_costs` (đã ship); null = chưa ship
     * @return array{cogs:int,platform_fee:int,shipping_fee:int,estimated_profit:int,platform_fee_pct:float,cost_complete:bool,cost_source:string}
     */
    private function compute(Order $order, Collection $items, array $platformFeePct, Collection $skuCosts, ?array $actual = null): array
    {
        $costSource = 'estimate';
        if ($actual !== null && $actual['cogs'] > 0) {
            $cogs = (int) $actual['cogs'];
            $complete = true;
            $costSource = $actual['source'];   // fifo | average | latest
        } else {
            $cogs = 0;
            $complete = $items->isNotEmpty();
            foreach ($items as $it) {
                $sku = $it->sku_id ? $skuCosts->get($it->sku_id) : null;
                $unit = $sku instanceof Sku ? $sku->effectiveCost() : 0;
                if ($unit <= 0) {
                    $complete = false;
                }
                $cogs += $unit * max(1, (int) $it->quantity);
            }
        }
        $pct = (float) ($platformFeePct[$order->source] ?? 0);
        $platformFee = (int) round((int) $order->grand_total * $pct / 100);
        $shipping = (int) $order->shipping_fee;

        return [
            'cogs' => $cogs,
            'platform_fee' => $platformFee,
            'shipping_fee' => $shipping,
            'estimated_profit' => (int) $order->grand_total - $platformFee - $shipping - $cogs,
            'platform_fee_pct' => $pct,
            'cost_complete' => $complete,
            'cost_source' => $costSource,
        ];
    }
}
