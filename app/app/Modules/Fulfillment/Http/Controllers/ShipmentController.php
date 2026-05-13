<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\ShipmentResource;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\PrintService;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Http\Resources\OrderResource;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** /api/v1/shipments + /orders/{id}/ship + /fulfillment/ready + /scan-pack. See SPEC 0006 §6. */
class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $service) {}

    /** GET /api/v1/fulfillment/ready — orders that need a parcel (processing/ready_to_ship, no open shipment). */
    public function ready(Request $request): JsonResponse
    {
        return $this->processing($request->merge(['stage' => 'prepare']));
    }

    /**
     * GET /api/v1/fulfillment/processing — the unified order-processing board (SPEC 0009).
     * One screen, one flow over the channel-order fulfillment lifecycle:
     *   stage=prepare  → đơn cần xử lý: status processing/ready_to_ship, chưa có vận đơn (hoặc đã có nhưng chưa in tem)
     *   stage=pack     → đã in tem, chờ đóng gói: vận đơn `created` & print_count>=1
     *   stage=handover → đã đóng gói, chờ bàn giao ĐVVC: vận đơn `packed`
     * Filters (mọi stage): `source` (csv nền tảng), `carrier` (csv ĐVVC), `customer` (LIKE tên/mã đơn),
     * `product` (LIKE tên SP / SKU sàn). Trả `OrderResource[]` (đã nạp `shipment`) — đồng nhất mọi stage.
     */
    public function processing(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền.');
        $stage = (string) $request->query('stage', 'prepare');

        $q = Order::query()->whereNull('deleted_at')->with(['channelAccount', 'shipments'])->withCount('items');
        $this->applyStageScope($q, $stage);
        $this->applyProcessingFilters($request, $q);
        $q->orderBy('placed_at')->orderBy('id');

        $perPage = min(200, max(1, (int) $request->query('per_page', 50)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => OrderResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()], 'stage' => $stage],
        ]);
    }

    /** GET /api/v1/fulfillment/processing/counts — badge counts per stage (same filters as processing). */
    public function processingCounts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền.');

        return response()->json(['data' => [
            'prepare' => $this->countForStage($request, 'prepare'),
            'pack' => $this->countForStage($request, 'pack'),
            'handover' => $this->countForStage($request, 'handover'),
        ]]);
    }

    private function countForStage(Request $request, string $stage): int
    {
        $q = Order::query()->whereNull('deleted_at');
        $this->applyStageScope($q, $stage);
        $this->applyProcessingFilters($request, $q);

        return $q->count();
    }

    /**
     * Stage scope for the processing board:
     *  - prepare: chưa có vận đơn open, HOẶC có vận đơn `created` chưa in tem (`print_count=0`) và có tem để in
     *    (`label_path` ≠ null — ĐVVC `manual` không có tem nên bỏ qua bước in, vào thẳng `pack`).
     *  - pack:    vận đơn `created` đã sẵn sàng đóng gói (đã in `print_count≥1`, hoặc không có tem để in / `manual`).
     *  - handover: vận đơn `packed`.
     *
     * @param  Builder<Order>  $q
     */
    private function applyStageScope($q, string $stage): void
    {
        $needsPrint = fn ($s) => $s->where('status', Shipment::STATUS_CREATED)->where('print_count', 0)->whereNotNull('label_path');
        match ($stage) {
            'pack' => $q->whereHas('shipments', fn ($s) => $s->where('status', Shipment::STATUS_CREATED)
                ->where(fn ($w) => $w->where('print_count', '>=', 1)->orWhereNull('label_path'))),
            'handover' => $q->whereHas('shipments', fn ($s) => $s->where('status', Shipment::STATUS_PACKED)),
            default => $q->whereIn('status', [S::Processing->value, S::ReadyToShip->value])
                ->where(fn ($w) => $w->whereDoesntHave('shipments', fn ($s) => $s->whereIn('status', Shipment::OPEN_STATUSES))
                    ->orWhereHas('shipments', $needsPrint)),
        };
    }

    /** @param Builder<Order> $q */
    private function applyProcessingFilters(Request $request, $q): void
    {
        $csv = fn (string $key) => array_values(array_filter(array_map('trim', explode(',', (string) $request->query($key, '')))));
        if ($sources = $csv('source')) {
            $q->whereIn('source', $sources);
        }
        if ($carriers = $csv('carrier')) {
            $q->whereHas('shipments', fn ($s) => $s->whereIn('carrier', $carriers)->whereIn('status', Shipment::OPEN_STATUSES));
        }
        if ($cid = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        if ($customer = trim((string) $request->query('customer', ''))) {
            $q->where(fn ($w) => $w->where('buyer_name', 'like', "%{$customer}%")->orWhere('order_number', 'like', "%{$customer}%")->orWhere('external_order_id', 'like', "%{$customer}%"));
        }
        if ($product = trim((string) $request->query('product', ''))) {
            $q->whereHas('items', fn ($i) => $i->where('name', 'like', "%{$product}%")->orWhere('seller_sku', 'like', "%{$product}%"));
        }
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền.');
        $q = Shipment::query()->with('order');
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($c = $request->query('carrier')) {
            $q->where('carrier', $c);
        }
        if ($oid = $request->query('order_id')) {
            $q->where('order_id', (int) $oid);
        }
        if ($term = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('tracking_no', 'like', "%{$term}%")
                ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$term}%")->orWhere('external_order_id', 'like', "%{$term}%")));
        }
        $q->orderByDesc('id');
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => ShipmentResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền.');

        return response()->json(['data' => new ShipmentResource(Shipment::query()->with(['order', 'events'])->findOrFail($id))]);
    }

    /** POST /api/v1/orders/{id}/ship */
    public function createForOrder(Request $request, int $orderId): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền tạo vận đơn.');
        $data = $request->validate([
            'carrier_account_id' => ['sometimes', 'nullable', 'integer'],
            'service' => ['sometimes', 'nullable', 'string', 'max:64'],
            'tracking_no' => ['sometimes', 'nullable', 'string', 'max:120'],
            'cod_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'weight_grams' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $order = Order::query()->whereNull('deleted_at')->findOrFail($orderId);
        try {
            $shipment = $this->service->createForOrder($order, $data['carrier_account_id'] ?? null, $data['service'] ?? null, [
                'tracking_no' => $data['tracking_no'] ?? null, 'cod_amount' => $data['cod_amount'] ?? null, 'weight_grams' => $data['weight_grams'] ?? null,
            ], $request->user()->getKey());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['order' => $e->getMessage()]);
        }

        return response()->json(['data' => new ShipmentResource($shipment->load(['order', 'events']))], 201);
    }

    /** POST /api/v1/shipments/bulk-create */
    public function bulkCreate(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền tạo vận đơn.');
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:200'],
            'order_ids.*' => ['integer'],
            'carrier_account_id' => ['sometimes', 'nullable', 'integer'],
            'service' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);
        $res = $this->service->bulkCreate((int) $tenant->id(), array_map('intval', $data['order_ids']), $data['carrier_account_id'] ?? null, $data['service'] ?? null, $request->user()->getKey());

        return response()->json(['data' => [
            'created' => ShipmentResource::collection(collect($res['created'])->each->load('order'))->resolve(),
            'errors' => $res['errors'],
        ]]);
    }

    public function track(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền.');
        $shipment = Shipment::query()->findOrFail($id);
        $this->service->syncTracking($shipment);

        return response()->json(['data' => new ShipmentResource($shipment->fresh()->load(['order', 'events']))]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền huỷ vận đơn.');
        $shipment = Shipment::query()->findOrFail($id);
        $this->service->cancel($shipment, $request->user()->getKey());

        return response()->json(['data' => new ShipmentResource($shipment->fresh()->load(['order', 'events']))]);
    }

    public function label(Request $request, int $id): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền in tem.');
        $shipment = Shipment::query()->findOrFail($id);
        abort_if($shipment->label_url === null, 404, 'Vận đơn chưa có tem.');

        return redirect()->away($shipment->label_url);
    }

    /** POST /api/v1/shipments/pack { shipment_ids } — bulk "đóng gói" (created → packed). */
    public function pack(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.scan') || $request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền đóng gói.');
        $data = $request->validate(['shipment_ids' => ['required', 'array', 'min:1', 'max:500'], 'shipment_ids.*' => ['integer']]);
        $n = 0;
        foreach (Shipment::query()->whereIn('id', array_map('intval', $data['shipment_ids']))->get() as $shipment) {
            try {
                if ($this->service->markPacked($shipment, 'user', $request->user()->getKey())) {
                    $n++;
                }
            } catch (\Throwable) {
            }
        }

        return response()->json(['data' => ['packed' => $n]]);
    }

    /** POST /api/v1/shipments/handover { shipment_ids } — bulk "bàn giao ĐVVC" (created/packed → picked_up, đơn shipped). */
    public function handover(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền bàn giao.');
        $data = $request->validate(['shipment_ids' => ['required', 'array', 'min:1', 'max:500'], 'shipment_ids.*' => ['integer']]);
        $n = 0;
        foreach (Shipment::query()->whereIn('id', array_map('intval', $data['shipment_ids']))->get() as $shipment) {
            try {
                if ($this->service->handover($shipment, 'system', $request->user()->getKey())) {
                    $n++;
                }
            } catch (\Throwable) {
            }
        }

        return response()->json(['data' => ['handed_over' => $n]]);
    }

    /**
     * POST /api/v1/shipments/bulk-refetch-slip { order_ids } — "Nhận phiếu giao hàng": với mỗi đơn đã "Chuẩn bị
     * hàng", thử kéo tem/AWB thật của sàn về; đơn nào vẫn chưa có phiếu ⇒ gom lại render **một** print job
     * `delivery` (FE hiện thanh tiến trình + nút "Mở để in" khi xong). Lỗi từng đơn ⇒ `errors[]`, không chặn batch.
     * Trả `{ ok, errors, print_job_id }` (`print_job_id` = null nếu mọi đơn đã có phiếu sẵn). SPEC 0013.
     */
    public function bulkRefetchSlip(Request $request, CurrentTenant $tenant, PrintService $print): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền.');
        $data = $request->validate(['order_ids' => ['required', 'array', 'min:1', 'max:200'], 'order_ids.*' => ['integer']]);
        $ok = 0;
        $errors = [];
        $needSlip = [];
        foreach (Order::query()->whereNull('deleted_at')->whereIn('id', array_map('intval', $data['order_ids']))->get() as $order) {
            try {
                $r = $this->service->refetchSlip($order, $request->user()->getKey());
                if ($r === 'no_shipment') {
                    $errors[] = ['order_id' => (int) $order->getKey(), 'message' => 'Đơn chưa được chuẩn bị hàng.'];

                    continue;
                }
                if ($r === 'pending_marketplace') {
                    // Đơn sàn chưa có tem thật của sàn — KHÔNG tạo phiếu tạm (rule cố định). User retry sau khi
                    // sàn cấp tracking / hết rate-limit. Reason trong order.issue_reason đã gắn ở arrangeOnChannel.
                    $errors[] = [
                        'order_id' => (int) $order->getKey(),
                        'message' => $order->issue_reason ?: 'Sàn chưa cấp phiếu giao hàng cho đơn này — thử lại sau ít phút.',
                    ];

                    continue;
                }
                $ok++;
                if ($r === 'need_slip') {
                    $needSlip[] = (int) $order->getKey();   // chỉ đơn manual mới có path này
                }
            } catch (\Throwable $e) {
                $errors[] = ['order_id' => (int) $order->getKey(), 'message' => $e->getMessage()];
            }
        }
        $jobId = $needSlip !== []
            ? (int) $print->createJob((int) $tenant->id(), PrintJob::TYPE_DELIVERY, $needSlip, [], $request->user()->getKey())->getKey()
            : null;

        return response()->json(['data' => ['ok' => $ok, 'errors' => $errors, 'print_job_id' => $jobId]]);
    }

    /** POST /api/v1/scan-pack { code } — quét mã vận đơn/mã đơn để đánh dấu ĐÃ ĐÓNG GÓI (created → packed). */
    public function scanPack(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.scan'), 403, 'Bạn không có quyền quét đóng gói.');

        return $this->scan($request, (int) $tenant->id(), action: 'pack');
    }

    /** POST /api/v1/scan-handover { code } — (app gọi) quét mã để BÀN GIAO ĐVVC (created/packed → picked_up, đơn shipped). */
    public function scanHandover(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship') || $request->user()?->can('fulfillment.scan'), 403, 'Bạn không có quyền bàn giao.');

        return $this->scan($request, (int) $tenant->id(), action: 'handover');
    }

    /** Shared scan handler for /scan-pack & /scan-handover. */
    private function scan(Request $request, int $tenantId, string $action): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:120']]);
        $shipment = $this->service->findByScanCode($tenantId, $data['code']);
        abort_if($shipment === null, 404, 'Không tìm thấy vận đơn hoặc đơn ứng với mã đã quét.');
        if ($shipment->isCancelled()) {
            abort(409, 'Vận đơn đã huỷ.');
        }
        $userId = $request->user()->getKey();
        if ($action === 'handover') {
            if (in_array($shipment->status, Shipment::HANDED_OVER_STATUSES, true)) {
                abort(409, 'Vận đơn này đã được bàn giao trước đó.');
            }
            $this->service->handover($shipment, 'user', $userId, 'packed_scanned');
            $msg = 'Đã bàn giao đơn';
        } else {
            if (in_array($shipment->status, [Shipment::STATUS_PACKED, ...Shipment::HANDED_OVER_STATUSES], true)) {
                abort(409, $shipment->status === Shipment::STATUS_PACKED ? 'Đơn này đã được đóng gói trước đó.' : 'Đơn này đã được bàn giao trước đó.');
            }
            $this->service->markPacked($shipment, 'user', $userId);
            $msg = 'Đã đóng gói đơn';
        }
        $shipment->refresh()->load(['order', 'events']);

        return response()->json(['data' => [
            'action' => $action,
            'message' => $msg,
            'shipment' => new ShipmentResource($shipment),
            'order' => $shipment->order ? ['id' => $shipment->order->id, 'order_number' => $shipment->order->order_number ?? $shipment->order->external_order_id, 'status' => $shipment->order->status->value] : null,
        ]]);
    }
}
