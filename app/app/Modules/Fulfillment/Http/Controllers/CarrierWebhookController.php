<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use Carbon\Carbon;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Models\ShipmentEvent;
use CMBcoreSeller\Modules\Fulfillment\Services\OrderStatusSync;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
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

        // Parse payload chuẩn hoá qua connector trước (mọi mode đều cần tracking_no).
        $event = $connector->parseWebhook($request);
        $tracking = (string) ($event['tracking_no'] ?? '');

        // Connector tự khai báo cách xác thực + resolve tenant (core không hard-code tên carrier).
        $signatureError = null;
        $shipment = match ($connector->webhookAuthMode()) {
            'tracking_lookup' => $this->resolveByTrackingLookup($request, $carrier, $tracking, $event, $signatureError),
            default => $this->resolveByTokenHeader($request, $carrier, $tracking, $signatureError),
        };
        if ($signatureError !== null) {
            return response()->json(['error' => $signatureError], 401);
        }
        if (! $shipment) {
            // Không tìm thấy vận đơn (hoặc thiếu tracking) ⇒ ack 200 để ĐVVC không retry storm.
            return response()->json(['data' => ['acknowledged' => true]]);
        }

        $newStatus = $event['status'] ?? null;
        $rawStatus = (string) ($event['raw_status'] ?? '');
        $occurredAt = isset($event['occurred_at']) ? Carbon::parse($event['occurred_at']) : now();

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
            app(OrderStatusSync::class);
            $this->syncOrderStatus($shipment, $newStatus);
        }

        // Task 10: COD/khoản-thất-bại/phí-hoàn có thể đến trên webhook không đổi status (vd cùng
        // status nhưng cập nhật số tiền) ⇒ ghi độc lập với block status ở trên. Chỉ ghi giá trị
        // non-null (không xoá dữ liệu đã có bằng null).
        $outcome = array_filter([
            'cod_collected' => $event['cod_collected'] ?? null,
            'failed_collect_collected' => $event['failed_collect_collected'] ?? null,
            'return_fee' => $event['return_fee'] ?? null,
        ], fn ($v) => $v !== null);
        if ($outcome !== []) {
            $shipment->forceFill($outcome)->save();
        }

        return response()->json(['data' => ['acknowledged' => true, 'shipment_id' => $shipment->getKey(), 'status' => $newStatus]]);
    }

    /**
     * GHN-style auth: header `Token` khớp `credentials.token` của 1 tenant ⇒ tìm vận đơn trong tenant đó.
     * Sai/thiếu token ⇒ gán $signatureError (caller trả 401). Trả Shipment|null.
     *
     * @param  array{code:string,message:string}|null  $signatureError
     */
    private function resolveByTokenHeader(Request $request, string $carrier, string $tracking, ?array &$signatureError): ?Shipment
    {
        $signatureError = null;
        $token = (string) $request->header('Token', '');
        if ($token === '') {
            $signatureError = ['code' => 'WEBHOOK_SIGNATURE_INVALID', 'message' => 'Thiếu token.'];

            return null;
        }
        $account = CarrierAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('carrier', $carrier)->where('is_active', true)->get()
            ->first(fn (CarrierAccount $a) => (($a->credentials ?? [])['token'] ?? null) === $token);
        if (! $account) {
            $signatureError = ['code' => 'WEBHOOK_SIGNATURE_INVALID', 'message' => 'Token không khớp.'];

            return null;
        }
        if ($tracking === '') {
            Log::info('carrier.webhook.no_tracking', ['carrier' => $carrier, 'tenant' => $account->tenant_id]);

            return null;
        }

        return $this->findShipment($carrier, $tracking, (int) $account->tenant_id);
    }

    /**
     * Tracking-lookup auth (GHTK, Viettel Post): webhook KHÔNG có Token header. Resolve tenant theo
     * tracking_no (label/ORDER_NUMBER duy nhất toàn hệ thống). Verify secret theo cách generic — core
     * KHÔNG hard-code tên carrier:
     *   - expected = credentials.webhook_secret (VTP) ?? credentials.client_source (GHTK)
     *   - incoming = $event['secret'] (connector parseWebhook trả, vd VTP body.TOKEN) ?? header X-Client-Source (GHTK)
     * Cả 2 non-empty mà khác ⇒ 401. Incoming rỗng ⇒ chấp nhận + log cảnh báo (hạn chế đã biết — spec GHTK §10).
     *
     * @param  array<string,mixed>  $event
     * @param  array{code:string,message:string}|null  $signatureError
     */
    private function resolveByTrackingLookup(Request $request, string $carrier, string $tracking, array $event, ?array &$signatureError): ?Shipment
    {
        $signatureError = null;
        if ($tracking === '') {
            Log::info('carrier.webhook.no_tracking', ['carrier' => $carrier]);

            return null;
        }
        $shipment = $this->findShipment($carrier, $tracking, null);
        if (! $shipment) {
            Log::info('carrier.webhook.shipment_not_found', ['carrier' => $carrier, 'tracking' => $tracking]);

            return null;
        }
        $account = CarrierAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('carrier', $carrier)->where('tenant_id', $shipment->tenant_id)->where('is_active', true)->first();
        $creds = $account ? (array) ($account->credentials ?? []) : [];
        $expected = (string) ($creds['webhook_secret'] ?? $creds['client_source'] ?? '');
        $incoming = (string) ($event['secret'] ?? $request->header('X-Client-Source', ''));
        if ($incoming !== '' && $expected !== '' && $incoming !== $expected) {
            $signatureError = ['code' => 'WEBHOOK_SIGNATURE_INVALID', 'message' => 'Chữ ký/secret webhook không khớp.'];

            return null;
        }
        if ($incoming === '') {
            Log::warning('carrier.webhook.unverified', ['carrier' => $carrier, 'tracking' => $tracking, 'tenant' => $shipment->tenant_id]);
        }

        return $shipment;
    }

    /** SPEC 0021 — đơn manual có carrier prefix 'manual_<code>' ⇒ match cả 2 dạng. */
    private function findShipment(string $carrier, string $tracking, ?int $tenantId): ?Shipment
    {
        return Shipment::query()->withoutGlobalScope(TenantScope::class)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('carrier', [$carrier, 'manual_'.$carrier])
            ->where('tracking_no', $tracking)
            ->open()
            ->first();
    }

    /** Reuse logic sync order status (lite version of ShipmentService::syncOrderToShipmentStatus). */
    private function syncOrderStatus(Shipment $shipment, string $shipmentStatus): void
    {
        $order = Order::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $shipment->tenant_id)->whereNull('deleted_at')
            ->find($shipment->order_id);
        if (! $order) {
            return;
        }
        $map = [
            Shipment::STATUS_AWAITING_PICKUP => StandardOrderStatus::ReadyToShip,
            Shipment::STATUS_PICKED_UP => StandardOrderStatus::Shipped,
            Shipment::STATUS_IN_TRANSIT => StandardOrderStatus::Shipped,
            Shipment::STATUS_DELIVERED => StandardOrderStatus::Delivered,
            Shipment::STATUS_FAILED => StandardOrderStatus::DeliveryFailed,
            Shipment::STATUS_RETURNING => StandardOrderStatus::Returning,
            Shipment::STATUS_RETURNED => StandardOrderStatus::ReturnedRefunded,
        ];
        if ($to = $map[$shipmentStatus] ?? null) {
            app(OrderStatusSync::class)
                ->apply($order, $to, 'carrier');
        }
    }
}
