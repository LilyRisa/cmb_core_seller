<?php

namespace CMBcoreSeller\Modules\Reports\Http\Controllers;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Reports\Services\SalesReportService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * /api/v1/reports/{revenue|profit|top-products} — báo cáo bán hàng & lợi nhuận (Phase 6.1 / SPEC 0015).
 *
 * Permissions: `reports.view` đọc; `reports.export` cho CSV stream. Filters chung: `from`, `to` (ISO date,
 * mặc định 30 ngày gần nhất), `granularity` (day|week|month), `source`, `channel_account_id`. Đầu ra envelope
 * chuẩn `{ data: ... }`. CSV stream UTF-8 BOM (Excel mở thẳng tiếng Việt).
 */
class ReportController extends Controller
{
    public function __construct(private readonly SalesReportService $service) {}

    public function revenue(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403, 'Bạn không có quyền xem báo cáo.');
        [$from, $to, $granularity, $filters] = $this->parseFilters($request);
        $data = $this->service->revenue((int) $tenant->id(), $from, $to, $granularity, $filters);

        return response()->json(['data' => $data]);
    }

    public function profit(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403, 'Bạn không có quyền xem báo cáo.');
        [$from, $to, $granularity, $filters] = $this->parseFilters($request);
        $data = $this->service->profit((int) $tenant->id(), $from, $to, $granularity, $filters);

        return response()->json(['data' => $data]);
    }

    public function topProducts(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403, 'Bạn không có quyền xem báo cáo.');
        [$from, $to] = $this->parseFilters($request);
        $limit = (int) $request->query('limit', 20);
        $sortBy = (string) $request->query('sort_by', 'revenue');
        $data = $this->service->topProducts((int) $tenant->id(), $from, $to, $limit, $sortBy);

        return response()->json(['data' => $data]);
    }

    public function export(Request $request, CurrentTenant $tenant): StreamedResponse|JsonResponse
    {
        abort_unless($request->user()?->can('reports.export'), 403, 'Bạn không có quyền xuất báo cáo.');
        $type = (string) $request->query('type', 'revenue');
        [$from, $to, $granularity, $filters] = $this->parseFilters($request);
        $tenantId = (int) $tenant->id();

        if ($type === 'profit') {
            $r = $this->service->profit($tenantId, $from, $to, $granularity, $filters);
            $headers = ['Ngày', 'Doanh thu (VND)', 'Giá vốn (VND)', 'Lợi nhuận gộp (VND)', 'Biên LN (%)'];
            $rows = array_map(fn ($s) => [$s['date'], $s['revenue'], $s['cogs'], $s['gross_profit'], $s['margin_pct']], $r['series']);

            return $this->service->toCsv('bao-cao-loi-nhuan-'.$from->format('Ymd').'-'.$to->format('Ymd'), $headers, $rows);
        }
        if ($type === 'top-products') {
            $limit = (int) $request->query('limit', 50);
            $r = $this->service->topProducts($tenantId, $from, $to, $limit, (string) $request->query('sort_by', 'revenue'));
            $headers = ['Mã SKU', 'Tên SP', 'SL bán', 'Doanh thu (VND)', 'Giá vốn (VND)', 'Lợi nhuận gộp (VND)', 'Biên LN (%)'];
            $rows = array_map(fn ($i) => [
                $i['sku']['sku_code'] ?? '', $i['sku']['name'] ?? '', $i['qty'], $i['revenue'], $i['cogs'], $i['gross_profit'], $i['margin_pct'],
            ], $r['items']);

            return $this->service->toCsv('top-san-pham-'.$from->format('Ymd').'-'.$to->format('Ymd'), $headers, $rows);
        }
        // default = revenue
        $r = $this->service->revenue($tenantId, $from, $to, $granularity, $filters);
        $headers = ['Ngày', 'Số đơn', 'Doanh thu (VND)', 'Phí vận chuyển (VND)'];
        $rows = array_map(fn ($s) => [$s['date'], $s['orders'], $s['revenue'], $s['shipping_fee']], $r['series']);

        return $this->service->toCsv('bao-cao-doanh-thu-'.$from->format('Ymd').'-'.$to->format('Ymd'), $headers, $rows);
    }

    /** @return array{0:CarbonImmutable,1:CarbonImmutable,2:string,3:array<string,mixed>} */
    private function parseFilters(Request $request): array
    {
        $from = $request->query('from')
            ? CarbonImmutable::parse((string) $request->query('from'))->startOfDay()
            : CarbonImmutable::now()->subDays(30)->startOfDay();
        $to = $request->query('to')
            ? CarbonImmutable::parse((string) $request->query('to'))->endOfDay()
            : CarbonImmutable::now()->endOfDay();
        $granularity = (string) $request->query('granularity', 'day');
        $filters = array_filter([
            'source' => $request->query('source'),
            'channel_account_id' => $request->query('channel_account_id') ? (int) $request->query('channel_account_id') : null,
        ], fn ($v) => $v !== null && $v !== '');

        return [$from, $to, $granularity, $filters];
    }
}
