<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\PrintJobResource;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\PrintService;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/** /api/v1/print-jobs — bulk shipping-label / picking-list / packing-list PDFs. See SPEC 0006 §6. */
class PrintJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');
        $q = PrintJob::query();
        if ($t = $request->query('type')) {
            $q->where('type', $t);
        }
        $page = $q->orderByDesc('id')->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => PrintJobResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');

        return response()->json(['data' => new PrintJobResource(PrintJob::query()->findOrFail($id))]);
    }

    public function store(Request $request, PrintService $service, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền in.');
        $data = $request->validate([
            'type' => ['required', 'in:label,picking,packing,invoice,delivery'],
            'order_ids' => ['sometimes', 'array', 'max:500'],
            'order_ids.*' => ['integer'],
            'shipment_ids' => ['sometimes', 'array', 'max:500'],
            'shipment_ids.*' => ['integer'],
            'template_id' => ['sometimes', 'nullable', 'integer'],
        ]);
        $orderIds = array_map('intval', $data['order_ids'] ?? []);
        $shipmentIds = array_map('intval', $data['shipment_ids'] ?? []);
        if ($orderIds === [] && $shipmentIds === []) {
            throw ValidationException::withMessages(['order_ids' => 'Chọn ít nhất một đơn hoặc một vận đơn.']);
        }
        $meta = [];
        if (! empty($data['template_id'])) {
            if ($data['type'] !== 'delivery') {
                throw ValidationException::withMessages(['template_id' => 'template_id chỉ dùng cho type=delivery.']);
            }
            $tpl = ShippingLabelTemplate::query()
                ->where('tenant_id', $tenant->id())->find((int) $data['template_id']);
            if (! $tpl) {
                throw ValidationException::withMessages(['template_id' => 'Template không tồn tại.']);
            }
            $meta = ['template_id' => $tpl->id, 'template_name' => $tpl->name];
        }
        $job = $service->createJob((int) $tenant->id(), $data['type'], $orderIds, $shipmentIds, $request->user()->getKey(), $meta);

        return response()->json(['data' => new PrintJobResource($job)], 201);
    }

    /**
     * Trả HTML phiếu giao hàng (đơn manual) để IN PHÍA TRÌNH DUYỆT — máy in tự co theo khổ giấy
     * (responsive). Khác store() (render PDF cố định khổ qua Gotenberg).
     */
    public function deliveryHtml(Request $request, PrintService $service, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền in.');
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'order_ids.*' => ['integer'],
            'template_id' => ['sometimes', 'nullable', 'integer'],
        ]);
        try {
            $html = $service->deliverySlipHtml(
                (int) $tenant->id(),
                array_map('intval', $data['order_ids']),
                ! empty($data['template_id']) ? (int) $data['template_id'] : null,
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['order_ids' => $e->getMessage()]);
        }

        return response()->json(['data' => ['html' => $html]]);
    }

    /**
     * POST /api/v1/print-jobs/{id}/mark-printed { copies? } — "Đánh dấu các đơn đã in" (popup sau khi mở PDF):
     * cộng `print_count` cho các vận đơn trong phạm vi của print job (mặc định +1). SPEC 0013.
     */
    public function markPrinted(Request $request, int $id, PrintService $service, ShipmentService $shipmentService): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');
        $data = $request->validate(['copies' => ['sometimes', 'integer', 'min:1', 'max:50']]);
        $job = PrintJob::query()->findOrFail($id);
        $res = $service->markPrinted($job, (int) ($data['copies'] ?? 1));
        if ($job->type === PrintJob::TYPE_LABEL) {
            $shipmentService->autoReadyToShipAfterPrint($res['shipment_ids'], $request->user()?->getKey());
        }

        return response()->json(['data' => $res]);
    }

    public function download(Request $request, int $id, PrintService $service): JsonResponse|RedirectResponse|Response
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');
        $job = PrintJob::query()->findOrFail($id);
        if ($job->status !== PrintJob::STATUS_DONE || ! $job->file_url) {
            abort(409, 'Tệp in chưa sẵn sàng'.($job->status === PrintJob::STATUS_ERROR ? ' (lỗi: '.$job->error.')' : '.'));
        }
        // Ephemeral (template-rendered delivery slip for manual orders): bytes live 1h in Redis,
        // not on R2 — re-trigger print if cache expired.
        if (data_get($job->meta, 'ephemeral') === true) {
            $bytes = $service->ephemeralBytes($job);
            abort_if($bytes === null, 410, 'Phiếu in đã hết hạn (giữ tối đa 1 giờ) — vui lòng in lại.');

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="phieu-giao-hang-'.$id.'.pdf"',
                'Cache-Control' => 'no-store',
            ]);
        }

        return redirect()->away($job->file_url);
    }
}
