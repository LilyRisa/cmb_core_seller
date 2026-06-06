<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Services\ShopHealthAnalysisService;
use CMBcoreSeller\Modules\Channels\Services\ShopReportService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/channel-shop-report — "Báo cáo sàn" read-only: sức khỏe/hiệu suất/điểm phạt
 * của các gian hàng đã kết nối. POST .../{id}/ai-insight — phân tích AI cho 1 gian hàng.
 * Gated bởi `plan.feature:shop_health_reports`. SPEC 2026-06-06.
 */
class ShopReportController extends Controller
{
    public function __construct(
        private readonly ShopReportService $service,
        private readonly ShopHealthAnalysisService $analysis,
    ) {}

    public function index(CurrentTenant $tenant): JsonResponse
    {
        return response()->json(['data' => $this->service->forTenant((int) $tenant->id())]);
    }

    /** POST /api/v1/channel-shop-report/{id}/ai-insight — chấm điểm + khuyến nghị (rule + AI nếu có gói). */
    public function aiInsight(int $id, CurrentTenant $tenant): JsonResponse
    {
        $report = $this->service->reportForAccountId($id);
        abort_if($report === null, 404, 'Gian hàng không tồn tại hoặc không hỗ trợ báo cáo.');

        $analysis = $this->analysis->analyze((int) $tenant->id(), [
            'provider' => $report['provider'],
            'overall_label' => $report['overall_label'],
            'metrics' => $report['metrics'],
            'penalties' => $report['penalties'],
            'punishments' => $report['punishments'],
            'recent_penalty_events' => $report['recent_penalty_events'],
        ]);

        return response()->json(['data' => $analysis]);
    }
}
