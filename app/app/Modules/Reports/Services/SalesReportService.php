<?php

namespace CMBcoreSeller\Modules\Reports\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    /** @param  array{source?:string,channel_account_id?:int,warehouse_id?:int}  $filters @return array{from:string,to:string,granularity:string,totals:array,series:list<array>,by_source:list<array>} */
    public function revenue(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, string $granularity, array $filters = []): array
    {
        $granularity = in_array($granularity, self::GRANULARITIES, true) ? $granularity : 'day';
        $base = Order::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')->whereBetween('placed_at', [$from, $to])
            ->whereNotIn('status', ['cancelled']);
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

    /** Báo cáo lợi nhuận THỰC (chỉ đơn đã ship — có `order_costs`). Trả tỉ suất ln% trên doanh thu. */
    public function profit(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, string $granularity, array $filters = []): array
    {
        $granularity = in_array($granularity, self::GRANULARITIES, true) ? $granularity : 'day';
        // Chỉ tính đơn đã ship + có `order_costs` (LN thực). Join để gắn order header attrs.
        $base = OrderCost::withoutGlobalScope(TenantScope::class)
            ->join('orders', 'orders.id', '=', 'order_costs.order_id')
            ->where('order_costs.tenant_id', $tenantId)
            ->whereBetween('order_costs.shipped_at', [$from, $to])
            ->whereNull('orders.deleted_at');
        $this->applyFiltersJoined($base, $filters);

        $trunc = $this->truncDateSql('order_costs.shipped_at', $granularity);
        $series = (clone $base)->selectRaw("{$trunc} AS bucket, SUM(order_costs.qty * (SELECT oi.unit_price FROM order_items oi WHERE oi.id = order_costs.order_item_id)) AS revenue_line, SUM(order_costs.cogs_total) AS cogs")
            ->groupBy(DB::raw($trunc))->orderBy(DB::raw($trunc))->get()
            ->map(fn ($r) => [
                'date' => (string) $r->bucket,
                'revenue' => (int) ($r->revenue_line ?? 0),
                'cogs' => (int) $r->cogs,
                'gross_profit' => (int) (($r->revenue_line ?? 0) - $r->cogs),
                'margin_pct' => $r->revenue_line > 0 ? round(100.0 * ((float) $r->revenue_line - (float) $r->cogs) / (float) $r->revenue_line, 2) : 0,
            ])->all();

        $totalsRow = (clone $base)->selectRaw('SUM(order_costs.qty * (SELECT oi.unit_price FROM order_items oi WHERE oi.id = order_costs.order_item_id)) AS revenue, SUM(order_costs.cogs_total) AS cogs, COUNT(DISTINCT order_costs.order_id) AS orders')->first();
        $revenue = (int) ($totalsRow->revenue ?? 0);
        $cogs = (int) ($totalsRow->cogs ?? 0);
        $totals = [
            'orders' => (int) ($totalsRow->orders ?? 0),
            'revenue' => $revenue, 'cogs' => $cogs,
            'gross_profit' => $revenue - $cogs,
            'margin_pct' => $revenue > 0 ? round(100.0 * ($revenue - $cogs) / $revenue, 2) : 0,
        ];

        return [
            'from' => $from->toIso8601String(), 'to' => $to->toIso8601String(),
            'granularity' => $granularity,
            'totals' => $totals,
            'series' => $series,
        ];
    }

    /** Top SKU theo doanh thu / lợi nhuận thực. */
    public function topProducts(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, int $limit, string $sortBy = 'revenue'): array
    {
        $limit = max(1, min(100, $limit));
        $sortBy = in_array($sortBy, ['revenue', 'profit', 'qty'], true) ? $sortBy : 'revenue';
        // SKU bán: dựa trên `order_items` (tất cả đơn không huỷ) + join `order_costs` (đã ship — có cogs).
        $rows = OrderItem::withoutGlobalScope(TenantScope::class)
            ->select(['order_items.sku_id'])
            ->selectRaw('SUM(order_items.quantity) AS qty')
            ->selectRaw('SUM(order_items.subtotal) AS revenue')
            ->selectRaw('COALESCE(SUM(oc.cogs_total), 0) AS cogs')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('order_costs AS oc', 'oc.order_item_id', '=', 'order_items.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereNull('orders.deleted_at')
            ->whereNotIn('orders.status', ['cancelled'])
            ->whereBetween('orders.placed_at', [$from, $to])
            ->whereNotNull('order_items.sku_id')
            ->groupBy('order_items.sku_id')
            ->orderByDesc(DB::raw($sortBy === 'profit' ? '(SUM(order_items.subtotal) - COALESCE(SUM(oc.cogs_total), 0))' : ($sortBy === 'qty' ? 'SUM(order_items.quantity)' : 'SUM(order_items.subtotal)')))
            ->limit($limit)->get();

        $skuIds = $rows->pluck('sku_id')->all();
        $skus = $skuIds === [] ? collect()
            : \CMBcoreSeller\Modules\Inventory\Models\Sku::withoutGlobalScope(TenantScope::class)->whereIn('id', $skuIds)
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

    /** @param  \Illuminate\Database\Eloquent\Builder<Order>  $q */
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
    public function toCsv(string $name, array $headers, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
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
