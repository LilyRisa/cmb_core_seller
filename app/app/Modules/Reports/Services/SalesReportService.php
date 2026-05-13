<?php

namespace CMBcoreSeller\Modules\Reports\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

// (`Carbon` not used — typehints use `CarbonImmutable`.)

/**
 * Báo cáo bán hàng / lợi nhuận — Phase 6.1 / SPEC 0015.
 *
 *  - `revenue($from, $to, granularity, filters)` → series doanh thu + chi phí ước tính + breakdown theo sàn.
 *  - `profit(...)` → lợi nhuận THỰC (chỉ đơn đã ship, theo `order_costs`) + biên LN%.
 *  - `topProducts(...)` → SKU bán chạy theo doanh thu / lợi nhuận thực, limit N.
 *
 * Đầu vào time-zone: hiển thị `Asia/Ho_Chi_Minh` nhưng query trên cột UTC; granularity day/week/month
 * group bằng `DATE_TRUNC` (Postgres) / `DATE`/`strftime` (SQLite) — wrap qua `truncDateSql()` để portable.
 */
class SalesReportService
{
    public const GRANULARITIES = ['day', 'week', 'month'];

    /** Trạng thái đơn được loại khỏi báo cáo doanh thu / lợi nhuận (không tính).
     *  Theo `StandardOrderStatus`: `cancelled` (đã huỷ) + `returned_refunded` (đã trả/hoàn). */
    private const EXCLUDED_STATUSES = ['cancelled', 'returned_refunded'];

    /** @param  array{source?:string,channel_account_id?:int,warehouse_id?:int}  $filters @return array{from:string,to:string,granularity:string,totals:array,series:list<array>,by_source:list<array>} */
    public function revenue(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, string $granularity, array $filters = []): array
    {
        $granularity = in_array($granularity, self::GRANULARITIES, true) ? $granularity : 'day';
        $base = Order::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')->whereBetween('placed_at', [$from, $to])
            ->whereNotIn('status', self::EXCLUDED_STATUSES);
        $this->applyFilters($base, $filters);

        $trunc = $this->truncDateSql('placed_at', $granularity);
        $series = (clone $base)->selectRaw("{$trunc} AS bucket, COUNT(*) AS orders, COALESCE(SUM(grand_total),0) AS revenue, COALESCE(SUM(shipping_fee),0) AS shipping_fee")
            ->groupBy(DB::raw($trunc))->orderBy(DB::raw($trunc))->get()
            ->map(fn ($r) => ['date' => (string) $r->bucket, 'orders' => (int) $r->orders, 'revenue' => (int) $r->revenue, 'shipping_fee' => (int) $r->shipping_fee])
            ->all();

        $bySource = (clone $base)->selectRaw('source, COUNT(*) AS orders, COALESCE(SUM(grand_total),0) AS revenue')
            ->groupBy('source')->orderByDesc(DB::raw('SUM(grand_total)'))->get()
            ->map(fn ($r) => ['source' => (string) $r->source, 'orders' => (int) $r->orders, 'revenue' => (int) $r->revenue])->all();

        $totals = (clone $base)->selectRaw('COUNT(*) AS orders, COALESCE(SUM(grand_total),0) AS revenue, COALESCE(SUM(shipping_fee),0) AS shipping_fee, COALESCE(SUM(item_total),0) AS item_total, COALESCE(AVG(grand_total),0) AS avg_order_value')->first();

        return [
            'from' => $from->toIso8601String(), 'to' => $to->toIso8601String(),
            'granularity' => $granularity,
            'totals' => [
                'orders' => (int) ($totals->orders ?? 0),
                'revenue' => (int) ($totals->revenue ?? 0),
                'item_total' => (int) ($totals->item_total ?? 0),
                'shipping_fee' => (int) ($totals->shipping_fee ?? 0),
                'avg_order_value' => (int) round((float) ($totals->avg_order_value ?? 0)),
            ],
            'series' => $series,
            'by_source' => $bySource,
        ];
    }

    /**
     * Báo cáo lợi nhuận THỰC — chỉ đơn đã ship (có `order_costs`).
     *
     *  - Doanh thu = Σ `order_items.subtotal` (đã trừ discount; **không** dùng `unit_price × qty`).
     *  - COGS thực = Σ `order_costs.cogs_total` (FIFO khi ship).
     *  - Phí sàn / phí ship thực từ `settlement_lines` (SPEC 0016) — chỉ áp khi đã reconcile (`order_id IS NOT NULL`).
     *  - `gross_profit` = revenue − cogs · `net_profit` = revenue − cogs − fees − shipping_paid_to_carrier.
     *  - `margin_pct` = LN ròng / doanh thu × 100.
     */
    public function profit(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, string $granularity, array $filters = []): array
    {
        $granularity = in_array($granularity, self::GRANULARITIES, true) ? $granularity : 'day';
        // Chỉ tính đơn đã ship + có `order_costs` (LN thực). Join `order_items` để dùng `subtotal` chuẩn.
        $base = OrderCost::withoutGlobalScope(TenantScope::class)
            ->join('orders', 'orders.id', '=', 'order_costs.order_id')
            ->join('order_items', 'order_items.id', '=', 'order_costs.order_item_id')
            ->where('order_costs.tenant_id', $tenantId)
            ->whereBetween('order_costs.shipped_at', [$from, $to])
            ->whereNull('orders.deleted_at')
            ->whereNotIn('orders.status', self::EXCLUDED_STATUSES);
        $this->applyFiltersJoined($base, $filters);

        $trunc = $this->truncDateSql('order_costs.shipped_at', $granularity);
        $seriesRows = (clone $base)
            ->selectRaw("{$trunc} AS bucket, SUM(order_items.subtotal) AS revenue_line, SUM(order_costs.cogs_total) AS cogs, COUNT(DISTINCT order_costs.order_id) AS orders")
            ->groupBy(DB::raw($trunc))->orderBy(DB::raw($trunc))->get();

        $totalsRow = (clone $base)
            ->selectRaw('SUM(order_items.subtotal) AS revenue, SUM(order_costs.cogs_total) AS cogs, COUNT(DISTINCT order_costs.order_id) AS orders')
            ->first();

        // Phí thực từ đối soát — group theo cùng bucket như series (SPEC 0016).
        $orderIds = (clone $base)->pluck('order_costs.order_id')->unique()->values()->all();
        $feesByOrder = $this->fetchActualFeesByOrder($tenantId, $orderIds);   // [order_id => ['fees'=>int,'shipping'=>int]]

        // Để chia phí theo bucket: cần `shipped_at` ⇒ map order_id → bucket (lấy ngày ship sớm nhất của đơn).
        $bucketByOrder = (clone $base)->selectRaw("order_costs.order_id, MIN({$trunc}) AS bucket")
            ->groupBy('order_costs.order_id')->pluck('bucket', 'order_costs.order_id')->all();

        $feesByBucket = [];   // bucket => ['fees'=>int,'shipping'=>int]
        foreach ($feesByOrder as $oid => $f) {
            $b = (string) ($bucketByOrder[$oid] ?? '');
            if ($b === '') {
                continue;
            }
            $feesByBucket[$b] ??= ['fees' => 0, 'shipping' => 0];
            $feesByBucket[$b]['fees'] += (int) $f['fees'];
            $feesByBucket[$b]['shipping'] += (int) $f['shipping'];
        }

        $series = $seriesRows->map(function ($r) use ($feesByBucket) {
            $b = (string) $r->bucket;
            $revenue = (int) ($r->revenue_line ?? 0);
            $cogs = (int) $r->cogs;
            $fees = (int) ($feesByBucket[$b]['fees'] ?? 0);
            $shipping = (int) ($feesByBucket[$b]['shipping'] ?? 0);
            $gross = $revenue - $cogs;
            $net = $gross - $fees - $shipping;

            return [
                'date' => $b,
                'orders' => (int) $r->orders,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'fees' => $fees,
                'shipping' => $shipping,
                'gross_profit' => $gross,
                'net_profit' => $net,
                'margin_pct' => $revenue > 0 ? round(100.0 * $net / $revenue, 2) : 0,
            ];
        })->all();

        $revenue = (int) ($totalsRow->revenue ?? 0);
        $cogs = (int) ($totalsRow->cogs ?? 0);
        $feesTotal = array_sum(array_column($feesByOrder, 'fees'));
        $shippingTotal = array_sum(array_column($feesByOrder, 'shipping'));
        $gross = $revenue - $cogs;
        $net = $gross - $feesTotal - $shippingTotal;
        $totals = [
            'orders' => (int) ($totalsRow->orders ?? 0),
            'revenue' => $revenue, 'cogs' => $cogs,
            'fees' => $feesTotal, 'shipping' => $shippingTotal,
            'gross_profit' => $gross, 'net_profit' => $net,
            'margin_pct' => $revenue > 0 ? round(100.0 * $net / $revenue, 2) : 0,
            'fee_source' => $feesTotal > 0 || $shippingTotal > 0 ? 'settlement' : 'none',
        ];

        return [
            'from' => $from->toIso8601String(), 'to' => $to->toIso8601String(),
            'granularity' => $granularity,
            'totals' => $totals,
            'series' => $series,
        ];
    }

    /**
     * Tổng phí thực theo đơn từ `settlement_lines` đã reconcile (cùng convention `OrderProfitService::fetchActualFees`).
     * `commission|payment_fee|voucher_seller|adjustment` ⇒ `fees`; `shipping_fee` ⇒ `shipping`. Số âm → đảo dấu thành chi dương.
     *
     * @param  list<int>  $orderIds
     * @return array<int, array{fees:int,shipping:int}>
     */
    private function fetchActualFeesByOrder(int $tenantId, array $orderIds): array
    {
        if ($orderIds === [] || ! class_exists(SettlementLine::class)) {
            return [];
        }
        $rows = SettlementLine::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereIn('order_id', $orderIds)
            ->selectRaw('order_id, fee_type, SUM(amount) AS amount')
            ->groupBy('order_id', 'fee_type')->get();
        $out = [];
        foreach ($rows as $r) {
            $oid = (int) $r->order_id;
            $out[$oid] ??= ['fees' => 0, 'shipping' => 0];
            $a = (int) $r->amount;
            switch ($r->fee_type) {
                case 'commission':
                case 'payment_fee':
                case 'voucher_seller':
                case 'adjustment':
                    $out[$oid]['fees'] += abs($a);
                    break;
                case 'shipping_fee':
                    $out[$oid]['shipping'] += abs($a);
                    break;
            }
        }

        return $out;
    }

    /**
     * Top SKU theo doanh thu / lợi nhuận / số lượng. Lọc theo `source`/`channel_account_id` (cùng filter
     * với revenue/profit) để chip "Sàn TMĐT" trên trang Báo cáo ảnh hưởng đồng nhất 3 tab.
     *
     * @param  array{source?:string,channel_account_id?:int,warehouse_id?:int}  $filters
     */
    public function topProducts(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, int $limit, string $sortBy = 'revenue', array $filters = []): array
    {
        $limit = max(1, min(100, $limit));
        $sortBy = in_array($sortBy, ['revenue', 'profit', 'qty'], true) ? $sortBy : 'revenue';
        // SKU bán: dựa trên `order_items` (đơn không huỷ/trả) + join `order_costs` (đã ship — có cogs).
        $rows = OrderItem::withoutGlobalScope(TenantScope::class)
            ->select(['order_items.sku_id'])
            ->selectRaw('SUM(order_items.quantity) AS qty')
            ->selectRaw('SUM(order_items.subtotal) AS revenue')
            ->selectRaw('COALESCE(SUM(oc.cogs_total), 0) AS cogs')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('order_costs AS oc', 'oc.order_item_id', '=', 'order_items.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereNull('orders.deleted_at')
            ->whereNotIn('orders.status', self::EXCLUDED_STATUSES)
            ->whereBetween('orders.placed_at', [$from, $to])
            ->whereNotNull('order_items.sku_id');
        if (! empty($filters['source'])) {
            $rows->where('orders.source', $filters['source']);
        }
        if (! empty($filters['channel_account_id'])) {
            $rows->where('orders.channel_account_id', (int) $filters['channel_account_id']);
        }
        $rows = $rows->groupBy('order_items.sku_id')
            ->orderByDesc(DB::raw($sortBy === 'profit' ? '(SUM(order_items.subtotal) - COALESCE(SUM(oc.cogs_total), 0))' : ($sortBy === 'qty' ? 'SUM(order_items.quantity)' : 'SUM(order_items.subtotal)')))
            ->limit($limit)->get();

        $skuIds = $rows->pluck('sku_id')->all();
        $skus = $skuIds === [] ? collect()
            : Sku::withoutGlobalScope(TenantScope::class)->whereIn('id', $skuIds)
                ->get(['id', 'sku_code', 'name', 'image_url'])->keyBy('id');

        return [
            'from' => $from->toIso8601String(), 'to' => $to->toIso8601String(),
            'sort_by' => $sortBy, 'limit' => $limit,
            'items' => $rows->map(function ($r) use ($skus) {
                $sku = $skus->get($r->sku_id);
                $rev = (int) ($r->revenue ?? 0);
                $cogs = (int) ($r->cogs ?? 0);

                return [
                    'sku_id' => (int) $r->sku_id,
                    'sku' => $sku ? ['id' => $sku->id, 'sku_code' => $sku->sku_code, 'name' => $sku->name, 'image_url' => $sku->image_url] : null,
                    'qty' => (int) $r->qty,
                    'revenue' => $rev, 'cogs' => $cogs,
                    'gross_profit' => $rev - $cogs,
                    'margin_pct' => $rev > 0 ? round(100.0 * ($rev - $cogs) / $rev, 2) : 0,
                ];
            })->values()->all(),
        ];
    }

    /** @param  Builder<Order>  $q */
    private function applyFilters($q, array $f): void
    {
        if (! empty($f['source'])) {
            $q->where('source', $f['source']);
        }
        if (! empty($f['channel_account_id'])) {
            $q->where('channel_account_id', (int) $f['channel_account_id']);
        }
    }

    /** Variant cho join với `orders` table. @param  \Illuminate\Database\Eloquent\Builder<OrderCost>  $q */
    private function applyFiltersJoined($q, array $f): void
    {
        if (! empty($f['source'])) {
            $q->where('orders.source', $f['source']);
        }
        if (! empty($f['channel_account_id'])) {
            $q->where('orders.channel_account_id', (int) $f['channel_account_id']);
        }
    }

    /** DATE_TRUNC tương đương: postgres dùng `DATE_TRUNC('day', col)`, sqlite dùng `strftime('%Y-%m-%d', col)`. */
    private function truncDateSql(string $col, string $granularity): string
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            return "DATE_TRUNC('{$granularity}', {$col})";
        }
        // sqlite (test) + mysql fallback — group by `Y-m-d` cho day; tuần/tháng dùng strftime.
        $fmt = match ($granularity) {
            'week' => '%Y-%W', 'month' => '%Y-%m', default => '%Y-%m-%d',
        };

        return "strftime('{$fmt}', {$col})";
    }

    /** @param  list<array<string,mixed>>  $rows  data rows; first row used to detect headers. */
    public function toCsv(string $name, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $fh = fopen('php://output', 'w');
            // UTF-8 BOM cho Excel mở đúng tiếng Việt
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, $headers);
            foreach ($rows as $r) {
                fputcsv($fh, array_values($r));
            }
            fclose($fh);
        }, $name.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
