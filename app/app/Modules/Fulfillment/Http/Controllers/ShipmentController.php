<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\ShipmentResource;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Http\Resources\OrderResource;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
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
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền.');
        $q = Order::query()->whereNull('deleted_at')
            ->whereIn('status', [S::Processing->value, S::ReadyToShip->value])
            ->whereDoesntHave('shipments', fn ($s) => $s->whereIn('status', Shipment::OPEN_STATUSES))
            ->with(['channelAccount'])->withCount('items');
        if ($cid = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        if ($src = $request->query('source')) {
            $q->where('source', $src);
        }
        if ($term = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('order_number', 'like', "%{$term}%")->orWhere('external_order_id', 'like', "%{$term}%")->orWhere('buyer_name', 'like', "%{$term}%"));
        }
        $q->orderBy('placed_at')->orderBy('id');
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => OrderResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
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

    /** POST /api/v1/shipments/handover { shipment_ids } */
    public function handover(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền bàn giao.');
        $data = $request->validate(['shipment_ids' => ['required', 'array', 'min:1', 'max:500'], 'shipment_ids.*' => ['integer']]);
        $n = 0;
        foreach (Shipment::query()->whereIn('id', array_map('intval', $data['shipment_ids']))->get() as $shipment) {
            try {
                $this->service->handover($shipment, 'system', $request->user()->getKey());
                $n++;
            } catch (\Throwable) {
                // skip individual failures
            }
        }

        return response()->json(['data' => ['handed_over' => $n]]);
    }

    /** POST /api/v1/scan-pack { code } */
    public function scanPack(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.scan'), 403, 'Bạn không có quyền quét đóng gói.');
        $data = $request->validate(['code' => ['required', 'string', 'max:120']]);
        $shipment = $this->service->findByScanCode((int) $tenant->id(), $data['code']);
        abort_if($shipment === null, 404, 'Không tìm thấy vận đơn hoặc đơn ứng với mã đã quét.');
        if (in_array($shipment->status, [Shipment::STATUS_PICKED_UP, Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_DELIVERED], true)) {
            abort(409, 'Vận đơn này đã được quét/đóng gói trước đó.');
        }
        if ($shipment->isCancelled()) {
            abort(409, 'Vận đơn đã huỷ.');
        }
        $this->service->handover($shipment, 'user', $request->user()->getKey(), 'packed_scanned');
        $shipment->refresh()->load(['order', 'events']);

        return response()->json(['data' => [
            'shipment' => new ShipmentResource($shipment),
            'order' => $shipment->order ? ['id' => $shipment->order->id, 'order_number' => $shipment->order->order_number ?? $shipment->order->external_order_id, 'status' => $shipment->order->status->value] : null,
        ]]);
    }
}
