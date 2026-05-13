<?php

namespace CMBcoreSeller\Modules\Orders\Http\Controllers;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Orders\Services\OrderProfitService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GET /api/v1/dashboard/summary?range=7|30|90 — counts (cards) + to-do panel +
 * series cho biểu đồ (doanh thu / đơn / lợi nhuận thực) + delta vs kỳ trước +
 * top SKUs + breakdown theo sàn + tình trạng gian hàng. SPEC 0001 §6.
 *
 * `range` (mặc định 7) là số ngày gần nhất bao gồm hôm nay; "kỳ trước" là cùng
 * độ dài liền kề (vd 7d ⇒ 7 ngày trước đó) để tính delta % chuyên nghiệp.
 */
class DashboardController extends Controller
{
    private const ALLOWED_RANGES = [7, 30, 90];

    public function summary(Request $request, CurrentTenant $tenant, OrderProfitService $profit): JsonResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        $tenantId = (int) $tenant->id();
        $range = (int) $request->query('range', 7);
        if (! in_array($range, self::ALLOWED_RANGES, true)) {
            $range = 7;
        }
        $today = CarbonImmutable::now()->startOfDay();
        $now = CarbonImmutable::now();
        $from = $today->subDays($range - 1);
        $prevTo = $from->subSecond();
        $prevFrom = $from->subDays($range);

        $preShipment = array_map(fn (StandardOrderStatus $s) => $s->value, array_filter(
            StandardOrderStatus::cases(), fn ($s) => $s->isPreShipment()
        ));

        $tenantSettings = $tenant->get()?->settings;

        // --- Top tile counts (live, không phụ thuộc khoảng thời gian) ---
        $accounts = [
            'total' => ChannelAccount::query()->count(),
            'active' => ChannelAccount::query()->active()->count(),
            'needs_reconnect' => ChannelAccount::query()->where('status', ChannelAccount::STATUS_EXPIRED)->count(),
        ];
        $orderCounts = [
            'today' => Order::query()->where('placed_at', '>=', $today)->count(),
            'to_process' => Order::query()->statusIn($preShipment)->count(),
            'ready_to_ship' => Order::query()->where('status', StandardOrderStatus::ReadyToShip->value)->count(),
            'shipped' => Order::query()->where('status', StandardOrderStatus::Shipped->value)->count(),
            'has_issue' => Order::query()->where('has_issue', true)->count(),
            'unmapped' => Order::query()->where('has_issue', true)->where('issue_reason', 'SKU chưa ghép')->count(),
            'total' => Order::query()->count(),
        ];

        // --- KPIs trong khoảng (revenue/orders/AOV) — gọi 2 lần (kỳ này + kỳ trước) cho delta ---
        [$cur, $curSeries] = $this->revenueWindow($tenantId, $from, $now, $range, $profit, $tenantSettings);
        [$prev] = $this->revenueWindow($tenantId, $prevFrom, $prevTo, $range, $profit, $tenantSettings);

        // --- Lợi nhuận thực (FIFO) trong khoảng + series ---
        [$profitCur, $profitSeries] = $this->profitWindow($tenantId, $from, $now);
        [$profitPrev] = $this->profitWindow($tenantId, $prevFrom, $prevTo);

        // --- Breakdown theo sàn (revenue + orders, kỳ này) ---
        $bySource = Order::query()->where('placed_at', '>=', $from)->where('placed_at', '<=', $now)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('source, COUNT(*) AS orders, COALESCE(SUM(grand_total),0) AS revenue')
            ->groupBy('source')->orderByDesc(DB::raw('SUM(grand_total)'))->get()
            ->map(fn ($r) => ['source' => (string) $r->getAttribute('source'), 'orders' => (int) $r->getAttribute('orders'), 'revenue' => (int) $r->getAttribute('revenue')])
            ->all();

        // --- Top 5 SKUs theo doanh thu (kỳ này) ---
        $topSkus = $this->topSkus($tenantId, $from, $now, 5);

        return response()->json(['data' => [
            'range' => $range,
            'period' => ['from' => $from->toIso8601String(), 'to' => $now->toIso8601String()],
            'previous_period' => ['from' => $prevFrom->toIso8601String(), 'to' => $prevTo->toIso8601String()],
            'channel_accounts' => $accounts,
            'orders' => $orderCounts,
            'revenue_today' => (int) Order::query()
                ->where('placed_at', '>=', $today)
                ->whereNotIn('status', [StandardOrderStatus::Cancelled->value])
                ->sum('grand_total'),
            // Khối KPI có delta (so kỳ trước cùng độ dài) cho 4 thẻ đầu dashboard.
            'kpis' => [
                'revenue' => ['current' => $cur['revenue'], 'previous' => $prev['revenue']],
                'orders' => ['current' => $cur['orders'], 'previous' => $prev['orders']],
                'avg_order_value' => ['current' => $cur['avg_order_value'], 'previous' => $prev['avg_order_value']],
                'estimated_profit' => ['current' => $cur['estimated_profit'], 'previous' => $prev['estimated_profit']],
                'gross_profit' => ['current' => $profitCur['gross_profit'], 'previous' => $profitPrev['gross_profit']],
                'margin_pct' => ['current' => $profitCur['margin_pct'], 'previous' => $profitPrev['margin_pct']],
            ],
            // Series theo ngày — đã đệm 0 cho ngày trống để biểu đồ liền mạch.
            'series' => $this->mergeSeries($from, $now, $curSeries, $profitSeries),
            'by_source' => $bySource,
            'top_skus' => $topSkus,
        ]]);
    }

    /**
     * @return array{0: array{orders:int,revenue:int,avg_order_value:int,estimated_profit:int}, 1: array<string, array{orders:int, revenue:int, estimated_profit:int}>}
     */
    private function revenueWindow(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, int $rangeDays, OrderProfitService $profit, ?array $tenantSettings): array
    {
        $base = Order::query()->where('placed_at', '>=', $from)->where('placed_at', '<=', $to)
            ->whereNotIn('status', ['cancelled']);

        $totalsRow = (clone $base)->selectRaw('COUNT(*) AS orders, COALESCE(SUM(grand_total),0) AS revenue, COALESCE(AVG(grand_total),0) AS aov')->first();
        $orders = (int) ($totalsRow?->getAttribute('orders') ?? 0);
        $revenue = (int) ($totalsRow?->getAttribute('revenue') ?? 0);
        $aov = (int) round((float) ($totalsRow?->getAttribute('aov') ?? 0));

        // Lợi nhuận ƯỚC TÍNH (sau phí sàn): tính qua OrderProfitService cho mọi đơn trong khoảng — dùng cho cả KPI lẫn series.
        $orderRows = (clone $base)->select(['id', 'tenant_id', 'source', 'channel_account_id', 'grand_total', 'shipping_fee', 'placed_at'])->get();
        $profit->annotateFromBatch($orderRows, $tenantSettings);
        $estimatedTotal = 0;
        $byDay = [];   // YYYY-MM-DD => ['orders'=>n,'revenue'=>n,'estimated_profit'=>n]
        foreach ($orderRows as $o) {
            $day = $o->placed_at?->copy()->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d') ?? '';
            $est = (int) (($o->getAttribute('_profit') ?? [])['estimated_profit'] ?? 0);
            $estimatedTotal += $est;
            $byDay[$day] ??= ['orders' => 0, 'revenue' => 0, 'estimated_profit' => 0];
            $byDay[$day]['orders']++;
            $byDay[$day]['revenue'] += (int) $o->grand_total;
            $byDay[$day]['estimated_profit'] += $est;
        }
        unset($rangeDays);

        return [
            ['orders' => $orders, 'revenue' => $revenue, 'avg_order_value' => $aov, 'estimated_profit' => $estimatedTotal],
            $byDay,
        ];
    }

    /**
     * @return array{0: array{orders:int, revenue:int, cogs:int, gross_profit:int, margin_pct:float}, 1: array<string, array{revenue:int, cogs:int, gross_profit:int}>}
     */
    private function profitWindow(int $tenantId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $base = OrderCost::withoutGlobalScope(TenantScope::class)
            ->join('orders', 'orders.id', '=', 'order_costs.order_id')
            ->where('order_costs.tenant_id', $tenantId)
            ->whereBetween('order_costs.shipped_at', [$from, $to])
            ->whereNull('orders.deleted_at');

        $driver = DB::connection()->getDriverName();
        $trunc = $driver === 'pgsql' ? "DATE_TRUNC('day', order_costs.shipped_at)" : "strftime('%Y-%m-%d', order_costs.shipped_at)";
        $rows = (clone $base)->selectRaw("{$trunc} AS bucket, SUM(order_costs.qty * (SELECT oi.unit_price FROM order_items oi WHERE oi.id = order_costs.order_item_id)) AS revenue, SUM(order_costs.cogs_total) AS cogs")
            ->groupBy(DB::raw($trunc))->orderBy(DB::raw($trunc))->get();

        $byDay = [];
        $revTotal = 0;
        $cogsTotal = 0;
        foreach ($rows as $r) {
            $day = substr((string) $r->getAttribute('bucket'), 0, 10);
            $rev = (int) ($r->getAttribute('revenue') ?? 0);
            $cogs = (int) ($r->getAttribute('cogs') ?? 0);
            $byDay[$day] = ['revenue' => $rev, 'cogs' => $cogs, 'gross_profit' => $rev - $cogs];
            $revTotal += $rev;
            $cogsTotal += $cogs;
        }
        $orders = (int) (clone $base)->distinct('order_costs.order_id')->count('order_costs.order_id');

        return [
            ['orders' => $orders, 'revenue' => $revTotal, 'cogs' => $cogsTotal, 'gross_profit' => $revTotal - $cogsTotal,
                'margin_pct' => $revTotal > 0 ? round(100.0 * ($revTotal - $cogsTotal) / $revTotal, 2) : 0.0],
            $byDay,
        ];
    }

    /**
     * Đệm 0 cho mọi ngày trong khoảng để biểu đồ liền mạch. Mỗi item chứa cả revenue/orders/estimated_profit/gross_profit.
     *
     * @param  array<string, array{orders:int, revenue:int, estimated_profit:int}>  $rev
     * @param  array<string, array{revenue:int, cogs:int, gross_profit:int}>  $prof
     * @return list<array{date:string, orders:int, revenue:int, estimated_profit:int, gross_profit:int}>
     */
    private function mergeSeries(CarbonImmutable $from, CarbonImmutable $to, array $rev, array $prof): array
    {
        $out = [];
        $cursor = $from->startOfDay();
        $end = $to->startOfDay();
        while ($cursor <= $end) {
            $day = $cursor->format('Y-m-d');
            $r = $rev[$day] ?? ['orders' => 0, 'revenue' => 0, 'estimated_profit' => 0];
            $p = $prof[$day] ?? ['revenue' => 0, 'cogs' => 0, 'gross_profit' => 0];
            $out[] = [
                'date' => $day,
                'orders' => $r['orders'],
                'revenue' => $r['revenue'],
                'estimated_profit' => $r['estimated_profit'],
                'gross_profit' => $p['gross_profit'],
            ];
            $cursor = $cursor->addDay();
        }

        return $out;
    }

    /** @return list<array{sku_id:int, sku_code:string, name:string, image_url:?string, qty:int, revenue:int}> */
    private function topSkus(int $tenantId, CarbonImmutable $from, CarbonImmutable $to, int $limit): array
    {
        $rows = OrderItem::withoutGlobalScope(TenantScope::class)
            ->select(['order_items.sku_id'])
            ->selectRaw('SUM(order_items.quantity) AS qty')
            ->selectRaw('SUM(order_items.subtotal) AS revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.tenant_id', $tenantId)
            ->whereNull('orders.deleted_at')
            ->whereNotIn('orders.status', ['cancelled'])
            ->whereBetween('orders.placed_at', [$from, $to])
            ->whereNotNull('order_items.sku_id')
            ->groupBy('order_items.sku_id')
            ->orderByDesc(DB::raw('SUM(order_items.subtotal)'))
            ->limit($limit)->get();
        $skuIds = $rows->pluck('sku_id')->all();
        $skus = $skuIds === []
            ? collect()
            : Sku::withoutGlobalScope(TenantScope::class)->whereIn('id', $skuIds)->get(['id', 'sku_code', 'name', 'image_url'])->keyBy('id');

        return $rows->map(function ($r) use ($skus) {
            $s = $skus->get($r->sku_id);

            return [
                'sku_id' => (int) $r->sku_id,
                'sku_code' => $s instanceof Sku ? (string) $s->sku_code : '',
                'name' => $s instanceof Sku ? (string) $s->name : '',
                'image_url' => $s instanceof Sku ? $s->image_url : null,
                'qty' => (int) $r->getAttribute('qty'),
                'revenue' => (int) $r->getAttribute('revenue'),
            ];
        })->values()->all();
    }
}
