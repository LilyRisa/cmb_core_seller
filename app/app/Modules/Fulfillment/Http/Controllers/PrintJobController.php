<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\PrintJobResource;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Services\PrintService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            $tpl = \CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate::query()
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
     * POST /api/v1/print-jobs/{id}/mark-printed { copies? } — "Đánh dấu các đơn đã in" (popup sau khi mở PDF):
     * cộng `print_count` cho các vận đơn trong phạm vi của print job (mặc định +1). SPEC 0013.
     */
    public function markPrinted(Request $request, int $id, PrintService $service): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');
        $data = $request->validate(['copies' => ['sometimes', 'integer', 'min:1', 'max:50']]);
        $job = PrintJob::query()->findOrFail($id);

        return response()->json(['data' => $service->markPrinted($job, (int) ($data['copies'] ?? 1))]);
    }

    public function download(Request $request, int $id): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');
        $job = PrintJob::query()->findOrFail($id);
        if ($job->status !== PrintJob::STATUS_DONE || ! $job->file_url) {
            abort(409, 'Tệp in chưa sẵn sàng'.($job->status === PrintJob::STATUS_ERROR ? ' (lỗi: '.$job->error.')' : '.'));
        }

        return redirect()->away($job->file_url);
    }
}
