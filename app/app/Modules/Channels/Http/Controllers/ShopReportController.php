<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Services\ShopReportService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/channel-shop-report — "Báo cáo sàn" read-only: sức khỏe/hiệu suất/điểm phạt
 * của các gian hàng đã kết nối. Gated bởi `plan.feature:shop_health_reports`. SPEC 2026-06-06.
 */
class ShopReportController extends Controller
{
    public function __construct(private readonly ShopReportService $service) {}

    public function index(CurrentTenant $tenant): JsonResponse
    {
        return response()->json(['data' => $this->service->forTenant((int) $tenant->id())]);
    }
}
