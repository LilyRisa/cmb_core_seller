<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghn;

use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * GHN (Giao Hàng Nhanh) carrier connector — real public API. Credentials per tenant
 * CarrierAccount: `token` (API token) + `shop_id`. The shipment payload built by
 * ShipmentService must carry GHN address codes (`to_district_id`, `to_ward_code`) — v1
 * doesn't resolve free-text addresses to GHN codes; that's a follow-up. See SPEC 0006.
 */
class GhnConnector extends AbstractCarrierConnector
{
    public function code(): string
    {
        return 'ghn';
    }

    public function displayName(): string
    {
        return 'Giao Hàng Nhanh (GHN)';
    }

    public function capabilities(): array
    {
        return ['createShipment', 'getLabel', 'getTracking', 'cancel'];
    }

    private function client(array $account): GhnClient
    {
        $c = $account['credentials'] ?? [];
        $token = (string) ($c['token'] ?? '');
        if ($token === '') {
            throw new RuntimeException('Tài khoản GHN chưa có token.');
        }

        return new GhnClient($token, isset($c['shop_id']) ? (int) $c['shop_id'] : null);
    }

    public function createShipment(array $account, array $shipment): array
    {
        $r = $shipment['recipient'] ?? [];
        $p = $shipment['parcel'] ?? [];
        $cod = (int) ($shipment['cod_amount'] ?? 0);
        $payload = array_filter([
            'payment_type_id' => $cod > 0 ? 2 : 1,           // 2 = buyer pays (COD), 1 = shop pays
            'required_note' => $shipment['required_note'] ?? 'KHONGCHOXEMHANG',
            'to_name' => $r['name'] ?? null,
            'to_phone' => $r['phone'] ?? null,
            'to_address' => $r['address'] ?? null,
            'to_ward_code' => $r['ward_code'] ?? ($shipment['to_ward_code'] ?? null),
            'to_district_id' => isset($r['district_id']) ? (int) $r['district_id'] : ($shipment['to_district_id'] ?? null),
            'weight' => (int) ($p['weight_grams'] ?? 500),
            'length' => (int) ($p['length_cm'] ?? 10),
            'width' => (int) ($p['width_cm'] ?? 10),
            'height' => (int) ($p['height_cm'] ?? 10),
            'service_type_id' => isset($shipment['service']) ? (int) $shipment['service'] : 2,
            'cod_amount' => $cod,
            'content' => $shipment['content'] ?? null,
            'client_order_code' => $shipment['client_order_code'] ?? null,
            'items' => $shipment['items'] ?? null,
        ], fn ($v) => $v !== null);

        $data = $this->client($account)->createOrder($payload);
        $orderCode = (string) ($data['order_code'] ?? '');
        if ($orderCode === '') {
            throw new RuntimeException('GHN không trả về mã vận đơn.');
        }

        return [
            'tracking_no' => $orderCode,
            'carrier' => 'ghn',
            'status' => 'created',
            'fee' => (int) ($data['total_fee'] ?? 0),
            'raw' => $data,
        ];
    }

    public function getLabel(array $account, string $trackingNo, string $format = 'A6'): array
    {
        $client = $this->client($account);
        $token = $client->genPrintToken([$trackingNo]);
        $bytes = $client->printLabel($token, in_array(strtoupper($format), ['A5', 'A6'], true) ? strtoupper($format) : 'A6');

        return ['filename' => "ghn-{$trackingNo}.pdf", 'mime' => 'application/pdf', 'bytes' => $bytes];
    }

    public function getTracking(array $account, string $trackingNo): array
    {
        $data = $this->client($account)->orderDetail($trackingNo);
        $events = [];
        foreach ((array) ($data['log'] ?? []) as $log) {
            $code = (string) ($log['status'] ?? '');
            if ($code === '') {
                continue;
            }
            $events[] = [
                'code' => $code,
                'description' => $log['status'] ?? null,
                'status' => GhnStatusMap::toShipmentStatus($code),
                'occurred_at' => $this->parseTime($log['updated_date'] ?? null),
                'raw' => $log,
            ];
        }

        return [
            'status' => GhnStatusMap::toShipmentStatus($data['status'] ?? null),
            'events' => $events,
            'raw' => $data,
        ];
    }

    public function cancel(array $account, string $trackingNo): void
    {
        $this->client($account)->cancel($trackingNo);
    }

    private function parseTime(?string $v): string
    {
        try {
            return $v ? Carbon::parse($v)->toIso8601String() : now()->toIso8601String();
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }
}
