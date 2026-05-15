<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Models\ShipmentEvent;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * /webhook/carriers/{carrier} — nhận status push từ ĐVVC (GHN, sau này GHTK/J&T). SPEC 0021.
 *
 * Core không hard-code 'ghn': connector tự khai báo capability `webhook` + tự parse payload qua
 * `CarrierConnector::parseWebhook`. Verify: header `Token` so với `carrier_accounts.credentials.token`
 * — match đúng 1 tenant. Sai/không match ⇒ `401` (chống spoof). Idempotent qua unique
 * `(shipment_id, code, occurred_at)` của shipment_events.
 *
 * Trả `200` nhanh kể cả khi không match shipment (tránh ĐVVC retry storm); ghi log để theo dõi.
 */
class CarrierWebhookController extends Controller
{
    public function __construct(protected CarrierRegistry $carriers) {}

    public function handle(Request $request, string $carrier): JsonResponse
    {
        if (! $this->carriers->has($carrier)) {
            return response()->json(['error' => ['code' => 'CARRIER_NOT_ENABLED', 'message' => 'Carrier không được bật.']], 404);
        }
        $connector = $this->carriers->for($carrier);
        if (! $connector instanceof AbstractCarrierConnector || ! $connector->supports('webhook')) {
            return response()->json(['error' => ['code' => 'CARRIER_NO_WEBHOOK', 'message' => 'Carrier này chưa hỗ trợ webhook.']], 404);
        }

        // Verify: tìm carrier_account trong tenant nào có credentials.token = header `Token` (GHN dùng
        // header này). Khác carrier có thể đổi key — nâng cấp sau khi connector trả `webhookAuth()`.
        $token = (string) $request->header('Token', '');
        if ($token === '') {
            return response()->json(['error' => ['code' => 'WEBHOOK_SIGNATURE_INVALID', 'message' => 'Thiếu token.']], 401);
        }
        $account = CarrierAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('carrier', $carrier)->where('is_active', true)->get()
            ->first(function (CarrierAccount $a) use ($token) {
                $cred = $a->credentials ?? [];

                return ($cred['token'] ?? null) === $token;
            });
        if (! $account) {
            // Không match tenant ⇒ 401, KHÔNG lưu, KHÔNG xử lý.
            return response()->json(['error' => ['code' => 'WEBHOOK_SIGNATURE_INVALID', 'message' => 'Token không khớp.']], 401);
        }

        // Parse payload chuẩn hoá qua connector.
        $event = $connector->parseWebhook($request);
        $tracking = (string) ($event['tracking_no'] ?? '');
        $newStatus = $event['status'] ?? null;
        $rawStatus = (string) ($event['raw_status'] ?? '');
        $occurredAt = isset($event['occurred_at']) ? \Carbon\Carbon::parse($event['occurred_at']) : now();

        if ($tracking === '') {
            Log::info('carrier.webhook.no_tracking', ['carrier' => $carrier, 'tenant' => $account->tenant_id]);

            return response()->json(['data' => ['acknowledged' => true]]); // ack để ĐVVC không retry
        }

        $shipment = Shipment::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $account->tenant_id)
            ->where('carrier', $carrier)
            ->where('tracking_no', $tracking)
            ->open()
            ->first();
        if (! $shipment) {
            Log::info('carrier.webhook.shipment_not_found', ['carrier' => $carrier, 'tracking' => $tracking, 'tenant' => $account->tenant_id]);

            return response()->json(['data' => ['acknowledged' => true]]);
        }

        // Idempotent: dedupe theo (shipment_id, code, occurred_at).
        ShipmentEvent::query()->withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['shipment_id' => $shipment->getKey(), 'code' => $rawStatus ?: 'update', 'occurred_at' => $occurredAt],
            [
                'tenant_id' => $shipment->tenant_id,
                'description' => $rawStatus,
                'status' => $newStatus,
                'source' => ShipmentEvent::SOURCE_CARRIER,
                'raw' => $event['raw'] ?? null,
                'created_at' => now(),
            ],
        );

        // Cập nhật shipment.status nếu connector trả status đã chuẩn hoá & khác hiện tại.
        if ($newStatus && $newStatus !== $shipment->status) {
            $attrs = ['status' => $newStatus];
            if ($newStatus === Shipment::STATUS_PICKED_UP && $shipment->picked_up_at === null) {
                $attrs['picked_up_at'] = $occurredAt;
            }
            if ($newStatus === Shipment::STATUS_DELIVERED && $shipment->delivered_at === null) {
                $attrs['delivered_at'] = $occurredAt;
            }
            $shipment->forceFill($attrs)->save();
            // Sync order status qua service (reuse logic syncOrderToShipmentStatus đã có).
            app(\CMBcoreSeller\Modules\Fulfillment\Services\OrderStatusSync::class);
            $this->syncOrderStatus($shipment, $newStatus);
        }

        return response()->json(['data' => ['acknowledged' => true, 'shipment_id' => $shipment->getKey(), 'status' => $newStatus]]);
    }

    /** Reuse logic sync order status (lite version of ShipmentService::syncOrderToShipmentStatus). */
    private function syncOrderStatus(Shipment $shipment, string $shipmentStatus): void
    {
        $order = \CMBcoreSeller\Modules\Orders\Models\Order::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $shipment->tenant_id)->whereNull('deleted_at')
            ->find($shipment->order_id);
        if (! $order) {
            return;
        }
        $map = [
            Shipment::STATUS_AWAITING_PICKUP => \CMBcoreSeller\Support\Enums\StandardOrderStatus::ReadyToShip,
            Shipment::STATUS_PICKED_UP => \CMBcoreSeller\Support\Enums\StandardOrderStatus::Shipped,
            Shipment::STATUS_IN_TRANSIT => \CMBcoreSeller\Support\Enums\StandardOrderStatus::Shipped,
            Shipment::STATUS_DELIVERED => \CMBcoreSeller\Support\Enums\StandardOrderStatus::Delivered,
            Shipment::STATUS_FAILED => \CMBcoreSeller\Support\Enums\StandardOrderStatus::DeliveryFailed,
            Shipment::STATUS_RETURNED => \CMBcoreSeller\Support\Enums\StandardOrderStatus::Returning,
        ];
        if ($to = $map[$shipmentStatus] ?? null) {
            app(\CMBcoreSeller\Modules\Fulfillment\Services\OrderStatusSync::class)
                ->apply($order, $to, 'carrier');
        }
    }
}
