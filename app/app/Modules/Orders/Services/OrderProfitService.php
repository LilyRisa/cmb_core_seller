<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
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
    /** Nhãn tiếng Việt cho từng loại phí trong breakdown. */
    public const FEE_LABELS = [
        'commission' => 'Hoa hồng / phí cố định',
        'transaction' => 'Phí giao dịch',
        'payment_fee' => 'Phí thanh toán',
        'service' => 'Phí dịch vụ (Voucher/Freeship Xtra)',
        'fixed' => 'Phí cố định / đơn',
        'shipping_fee' => 'Phí vận chuyển',
        'voucher_seller' => 'Voucher người bán',
        'adjustment' => 'Điều chỉnh',
    ];

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
     * Biểu phí ước tính theo sàn = mặc định `config('orders.fee_rates')` ∪ override tenant
     * (`settings.fee_rates[source]`). Dùng khi KHÔNG có `platform_fee_pct` legacy cho sàn đó.
     *
     * @param  array<string,mixed>|null  $tenantSettings
     * @return array<string, array{commission_pct:float,transaction_pct:float,service_pct:float,fixed_fee:int,programs:list<array<string,mixed>>}>
     */
    public function feeRates(?array $tenantSettings): array
    {
        $defaults = (array) config('orders.fee_rates', []);
        $override = (array) (($tenantSettings ?? [])['fee_rates'] ?? []);
        $sources = array_unique([...array_keys($defaults), ...array_keys($override)]);
        $out = [];
        foreach ($sources as $src) {
            $d = (array) ($defaults[$src] ?? []);
            $o = (array) ($override[$src] ?? []);
            $out[(string) $src] = [
                'commission_pct' => max(0.0, (float) ($o['commission_pct'] ?? $d['commission_pct'] ?? 0)),
                'transaction_pct' => max(0.0, (float) ($o['transaction_pct'] ?? $d['transaction_pct'] ?? 0)),
                'service_pct' => max(0.0, (float) ($o['service_pct'] ?? $d['service_pct'] ?? 0)),
                'fixed_fee' => max(0, (int) ($o['fixed_fee'] ?? $d['fixed_fee'] ?? 0)),
                'programs' => $this->mergePrograms((array) ($d['programs'] ?? []), (array) ($o['programs'] ?? [])),
            ];
        }

        return $out;
    }

    /**
     * Gộp phí chương trình tùy chọn: mặc định config + override tenant (theo `key`).
     * Tenant chỉ cần gửi {key, enabled, rate?} — phần còn lại (label/kind/cap/base) lấy từ config.
     *
     * @param  list<array<string,mixed>>  $defaults
     * @param  list<array<string,mixed>>  $override
     * @return list<array<string,mixed>>
     */
    private function mergePrograms(array $defaults, array $override): array
    {
        $byKey = [];
        foreach ($override as $p) {
            $p = (array) $p;
            if (isset($p['key'])) {
                $byKey[(string) $p['key']] = $p;
            }
        }
        $out = [];
        foreach ($defaults as $d) {
            $d = (array) $d;
            $key = (string) ($d['key'] ?? '');
            $o = $byKey[$key] ?? [];
            $out[] = [
                'key' => $key,
                'label' => (string) ($d['label'] ?? $key),
                'kind' => (string) ($d['kind'] ?? 'pct'),
                'base' => (string) ($d['base'] ?? 'item'),
                'cap_per_item' => isset($d['cap_per_item']) ? (int) $d['cap_per_item'] : null,
                'rate' => max(0.0, (float) ($o['rate'] ?? $d['rate'] ?? 0)),
                'enabled' => (bool) ($o['enabled'] ?? $d['enabled'] ?? false),
            ];
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
        $rates = $this->feeRates($tenantSettings);
        $orderIds = $orders->pluck('id')->all();
        $itemsByOrder = OrderItem::query()->whereIn('order_id', $orderIds)->get(['order_id', 'sku_id', 'quantity'])->groupBy('order_id');
        $costs = $this->fetchCosts($itemsByOrder->flatten(1)->pluck('sku_id'));
        $actualByOrder = $this->fetchActualCogs($orderIds);   // FIFO COGS thực — đơn đã ship (SPEC 0014)
        $feesByOrder = $this->fetchActualFees($orderIds);   // Phí thực từ đối soát sàn (SPEC 0016)
        $shipFeesByOrder = $this->fetchActualShipmentFees($orderIds);   // R2 (Sprint 4) — phí GHN thực sau khi createOrder
        $orders->each(fn (Order $o) => $o->setAttribute('_profit', $this->compute(
            $o, $itemsByOrder->get($o->getKey(), collect()), $pct, $costs,
            $actualByOrder[$o->getKey()] ?? null,
            $feesByOrder[$o->getKey()] ?? null,
            $shipFeesByOrder[$o->getKey()] ?? null,
            $rates,
        )));
    }

    /** Annotate a single order whose `items` relation is already loaded. */
    public function annotateLoaded(Order $order, ?array $tenantSettings): void
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        $actual = $this->fetchActualCogs([$order->getKey()])[$order->getKey()] ?? null;
        $fees = $this->fetchActualFees([$order->getKey()])[$order->getKey()] ?? null;
        $shipFee = $this->fetchActualShipmentFees([$order->getKey()])[$order->getKey()] ?? null;
        $order->setAttribute('_profit', $this->compute(
            $order, $items, $this->platformFeePct($tenantSettings), $this->fetchCosts($items->pluck('sku_id')), $actual, $fees, $shipFee,
            $this->feeRates($tenantSettings),
        ));
    }

    /**
     * R2 (Sprint 4) — phí ĐVVC thực tế từ shipments.fee. Đơn manual sau khi GHN createOrder ⇒ `shipments.fee`
     * có giá trị (phí GHN trả về). Khác `orders.shipping_fee` (số user tự nhập khi tạo đơn — có thể chưa
     * khớp với phí GHN cuối cùng). Dùng cho profit calculation chính xác hơn cho đơn manual.
     *
     * @param  list<int>  $orderIds
     * @return array<int, int> orderId => total fee from open shipments
     */
    private function fetchActualShipmentFees(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }
        $rows = Shipment::withoutGlobalScope(TenantScope::class)
            ->whereIn('order_id', $orderIds)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('order_id, COALESCE(SUM(fee), 0) AS fee')
            ->groupBy('order_id')
            ->get();
        $out = [];
        foreach ($rows as $r) {
            $fee = (int) ($r->fee ?? 0);
            if ($fee > 0) {
                $out[(int) $r->order_id] = $fee;
            }
        }

        return $out;
    }

    /**
     * Tổng phí THỰC theo đơn từ đối soát sàn (`settlement_lines`, đã reconcile). SPEC 0016.
     * Khi có ⇒ `fee_source='settlement'` và `platform_fee`/`shipping_fee` lấy từ đây (số ÂM → đảo dấu thành chi).
     *
     * @param  list<int>  $orderIds
     * @return array<int, array{platform_fee:int, shipping_fee:int, lines:array<string,int>}>
     */
    private function fetchActualFees(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }
        if (! class_exists(SettlementLine::class)) {
            return [];   // module Finance chưa enable
        }
        $rows = SettlementLine::withoutGlobalScope(TenantScope::class)
            ->whereIn('order_id', $orderIds)
            ->selectRaw('order_id, fee_type, SUM(amount) AS amount')
            ->groupBy('order_id', 'fee_type')->get();
        $out = [];
        foreach ($rows as $r) {
            $oid = (int) $r->order_id;
            $out[$oid] ??= ['platform_fee' => 0, 'shipping_fee' => 0, 'lines' => []];
            $a = (int) $r->amount;
            switch ($r->fee_type) {
                case 'commission':
                case 'payment_fee':
                case 'voucher_seller':
                case 'adjustment':
                    $out[$oid]['platform_fee'] += abs($a);   // chi → cộng vào fee dương để hiển thị
                    $out[$oid]['lines'][(string) $r->fee_type] = ($out[$oid]['lines'][(string) $r->fee_type] ?? 0) + abs($a);
                    break;
                case 'shipping_fee':
                    $out[$oid]['shipping_fee'] += abs($a);
                    break;
            }
        }

        return $out;
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
     * @param  array{platform_fee:int,shipping_fee:int,lines?:array<string,int>}|null  $actualFees  Phí THỰC từ đối soát (SPEC 0016)
     * @param  int|null  $actualShipFee  R2 (Sprint 4) — phí ĐVVC thực từ shipments.fee (manual: GHN trả về)
     * @param  array<string, array{commission_pct:float,transaction_pct:float,service_pct:float,fixed_fee:int,programs:list<array<string,mixed>>}>  $feeRates  biểu phí ước tính theo sàn
     * @return array{cogs:int,platform_fee:int,shipping_fee:int,estimated_profit:int,platform_fee_pct:float,cost_complete:bool,cost_source:string,fee_source:string,fee_breakdown:list<array{type:string,label:string,amount:int}>}
     */
    private function compute(Order $order, Collection $items, array $platformFeePct, Collection $skuCosts, ?array $actual = null, ?array $actualFees = null, ?int $actualShipFee = null, array $feeRates = []): array
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
        $grand = (int) $order->grand_total;
        $pct = (float) ($platformFeePct[$order->source] ?? 0);
        $feeSource = 'estimate';
        /** @var list<array{type:string,label:string,amount:int}> $breakdown */
        $breakdown = [];

        if ($actualFees !== null && ($actualFees['platform_fee'] > 0 || $actualFees['shipping_fee'] > 0)) {
            // (1) Phí THỰC từ đối soát sàn — chi tiết theo từng loại.
            $platformFee = (int) $actualFees['platform_fee'];
            $shipping = (int) $actualFees['shipping_fee'];
            $feeSource = 'settlement';
            foreach ((array) ($actualFees['lines'] ?? []) as $type => $amt) {
                $breakdown[] = ['type' => (string) $type, 'label' => self::FEE_LABELS[(string) $type] ?? (string) $type, 'amount' => (int) $amt];
            }
            $pct = $grand > 0 ? round($platformFee / $grand * 100, 1) : 0.0;
        } else {
            // Phí ship (giữ logic cũ): đơn manual ưu tiên shipments.fee (GHN trả) > orders.shipping_fee.
            if ($order->source === 'manual' && $actualShipFee !== null && $actualShipFee > 0) {
                $shipping = $actualShipFee;
                $feeSource = 'carrier';
            } else {
                $shipping = (int) $order->shipping_fee;
            }

            if (isset($platformFeePct[$order->source])) {
                // (2) LEGACY: tenant đã cấu hình 1 % phí phẳng ⇒ giữ NGUYÊN hành vi (SPEC 0012).
                $platformFee = (int) round($grand * $pct / 100);
                if ($platformFee > 0) {
                    $breakdown[] = ['type' => 'commission', 'label' => self::FEE_LABELS['commission'], 'amount' => $platformFee];
                }
            } else {
                // (3) ƯỚC TÍNH CHI TIẾT theo biểu phí sàn (hoa hồng / giao dịch / dịch vụ / cố định).
                $r = $feeRates[$order->source] ?? null;
                $platformFee = 0;
                if ($r !== null) {
                    $commissionBase = max(0, (int) $order->item_total - (int) $order->seller_discount);
                    $commission = (int) round($commissionBase * $r['commission_pct'] / 100);
                    $transaction = (int) round($grand * $r['transaction_pct'] / 100);
                    $service = $r['service_pct'] > 0 ? (int) round($commissionBase * $r['service_pct'] / 100) : 0;
                    $fixed = $grand > 0 ? (int) $r['fixed_fee'] : 0;
                    foreach ([['commission', $commission], ['transaction', $transaction], ['service', $service], ['fixed', $fixed]] as [$t, $amt]) {
                        if ($amt > 0) {
                            $breakdown[] = ['type' => $t, 'label' => self::FEE_LABELS[$t], 'amount' => $amt];
                        }
                    }
                    $platformFee = $commission + $transaction + $service + $fixed;

                    // Phí chương trình TÙY CHỌN (Voucher Xtra/Freeship Xtra/PiShip/Affiliate…) — chỉ khi shop bật.
                    $itemCount = max(1, $items->count());
                    foreach ($r['programs'] as $prog) {
                        if (empty($prog['enabled'])) {
                            continue;
                        }
                        if (((string) ($prog['kind'] ?? 'pct')) === 'fixed') {
                            $amt = $grand > 0 ? (int) round((float) ($prog['rate'] ?? 0)) : 0;
                        } else {
                            $base = ((string) ($prog['base'] ?? 'item')) === 'grand' ? $grand : $commissionBase;
                            $amt = (int) round($base * (float) ($prog['rate'] ?? 0) / 100);
                            $cap = $prog['cap_per_item'] ?? null;
                            if ($cap !== null && (int) $cap > 0) {
                                $amt = min($amt, (int) $cap * $itemCount);   // vd Voucher Xtra: tối đa 50k/SP
                            }
                        }
                        if ($amt > 0) {
                            $breakdown[] = ['type' => 'program:'.((string) ($prog['key'] ?? '')), 'label' => (string) ($prog['label'] ?? 'Phí chương trình'), 'amount' => $amt];
                            $platformFee += $amt;
                        }
                    }

                    $pct = $grand > 0 ? round($platformFee / $grand * 100, 1) : 0.0;
                }
            }
        }

        return [
            'cogs' => $cogs,
            'platform_fee' => $platformFee,
            'shipping_fee' => $shipping,
            'estimated_profit' => $grand - $platformFee - $shipping - $cogs,
            'platform_fee_pct' => $pct,
            'cost_complete' => $complete,
            'cost_source' => $costSource,
            'fee_source' => $feeSource,
            'fee_breakdown' => $breakdown,
        ];
    }
}
