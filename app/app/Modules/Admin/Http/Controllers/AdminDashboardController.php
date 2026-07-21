<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Admin\Services\AdminDashboardOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/** GET /api/v1/admin/dashboard/overview — số liệu trang "Tổng quan" admin (SPEC 2026-07-21). */
class AdminDashboardController extends Controller
{
    public function overview(AdminDashboardOverviewService $service): JsonResponse
    {
        // JSON_PRESERVE_ZERO_FRACTION: avg_resolution_hours là float (vd. 2.0 giờ) — mặc định
        // json_encode() làm tròn số nguyên "2.0" thành "2", khiến FE/test nhận int thay vì float.
        return response()->json(['data' => $service->overview()], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
