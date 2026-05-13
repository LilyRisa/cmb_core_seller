<?php

namespace CMBcoreSeller\Modules\Procurement\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Procurement\Services\DemandPlanningService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * /api/v1/procurement/demand-planning — đề xuất nhập hàng (Phase 6.3 — SPEC 0014b).
 *
 * Permissions: `procurement.view` đọc; `procurement.manage` mới tạo PO hàng loạt từ đề xuất.
 */
class DemandPlanningController extends Controller
{
    public function __construct(private readonly DemandPlanningService $service) {}

    public function index(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.view'), 403, 'Bạn không có quyền xem đề xuất nhập hàng.');
        $window = (int) $request->query('window_days', 30);
        $lead = (int) $request->query('lead_time', 7);
        $cover = (int) $request->query('cover_days', 14);
        $r = $this->service->compute((int) $tenant->id(), $window, $lead, $cover, [
            'q' => $request->query('q'),
            'supplier_id' => $request->query('supplier_id') ? (int) $request->query('supplier_id') : null,
            'urgency' => $request->query('urgency'),
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 50),
        ]);

        return response()->json([
            'data' => $r['items'],
            'meta' => [
                'pagination' => ['page' => $r['page'], 'per_page' => $r['per_page'], 'total' => $r['total'], 'total_pages' => (int) ceil($r['total'] / max(1, $r['per_page']))],
                'params' => $r['params'],
            ],
        ]);
    }

    public function createPo(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('procurement.manage'), 403, 'Bạn không có quyền tạo đơn mua.');
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.sku_id' => ['required', 'integer'],
            'rows.*.qty' => ['required', 'integer', 'min:1'],
            'rows.*.supplier_id' => ['required', 'integer'],
            'rows.*.unit_cost' => ['sometimes', 'integer', 'min:0'],
        ]);
        try {
            $ids = $this->service->createPoFromSuggestions((int) $tenant->id(), (int) $data['warehouse_id'], $data['rows'], $request->user()->getKey());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['rows' => $e->getMessage()]);
        }

        return response()->json(['data' => ['purchase_order_ids' => $ids, 'count' => count($ids)]], 201);
    }
}
