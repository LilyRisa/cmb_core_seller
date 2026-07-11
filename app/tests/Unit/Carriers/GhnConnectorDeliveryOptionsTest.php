<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use PHPUnit\Framework\TestCase;

class GhnConnectorDeliveryOptionsTest extends TestCase
{
    public function test_capability_includes_failed_delivery_collect(): void
    {
        $this->assertContains('failed_delivery_collect', (new GhnConnector)->capabilities());
    }

    public function test_build_ghn_payload_maps_delivery_options(): void
    {
        $connector = new class extends GhnConnector
        {
            public function exposeBuildGhnPayload(array $shipment, int $cod, array $items): array
            {
                return $this->buildGhnPayload($shipment, $cod, $items);
            }
        };

        $shipment = [
            'required_note' => 'CHOTHUHANG',
            'delivery_note' => 'Gọi trước',
            'failed_collect_amount' => 30000,
            'recipient' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000', 'address' => '123 Đường X'],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => '456 Đường Y'],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 0,
        ];

        $payload = $connector->exposeBuildGhnPayload($shipment, 0, [
            ['name' => 'Hàng', 'quantity' => 1, 'weight' => 200],
        ]);

        // Không COD ⇒ shop trả phí (1) — original COD-based default, không map fee_payer.
        $this->assertSame(1, $payload['payment_type_id']);
        $this->assertSame('CHOTHUHANG', $payload['required_note']);
        $this->assertSame('Gọi trước', $payload['note']);
        $this->assertSame(30000, $payload['cod_failed_amount']);
    }

    public function test_build_ghn_payload_defaults_payment_type_and_drops_empty_fields(): void
    {
        $connector = new class extends GhnConnector
        {
            public function exposeBuildGhnPayload(array $shipment, int $cod, array $items): array
            {
                return $this->buildGhnPayload($shipment, $cod, $items);
            }
        };

        $shipment = [
            'recipient' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000', 'address' => '123 Đường X'],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => '456 Đường Y'],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 50000,
        ];

        $payload = $connector->exposeBuildGhnPayload($shipment, 50000, [
            ['name' => 'Hàng', 'quantity' => 1, 'weight' => 200],
        ]);

        // Phí ship đã gộp vào COD ⇒ LUÔN shop trả phí (1), kể cả khi có COD — tránh thu chồng phí.
        $this->assertSame(1, $payload['payment_type_id']);
        $this->assertSame('KHONGCHOXEMHANG', $payload['required_note']);
        $this->assertArrayNotHasKey('note', $payload);
        $this->assertArrayNotHasKey('cod_failed_amount', $payload);
    }

    public function test_build_ghn_payload_maps_goods_type_and_pick_station(): void
    {
        $connector = new class extends GhnConnector
        {
            public function exposeBuildGhnPayload(array $shipment, int $cod, array $items): array
            {
                return $this->buildGhnPayload($shipment, $cod, $items);
            }
        };
        $base = [
            'recipient' => ['name' => 'A', 'phone' => '0900000000', 'address' => 'X'],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => 'Y'],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 0,
        ];
        $items = [['name' => 'H', 'quantity' => 1, 'weight' => 200]];

        // Hàng nặng ⇒ service_type_id 5; gửi tại điểm ⇒ pick_station_id.
        $heavy = $connector->exposeBuildGhnPayload($base + ['goods_type' => 'heavy', 'pick_station_id' => 12345], 0, $items);
        $this->assertSame(5, $heavy['service_type_id']);
        $this->assertSame(12345, $heavy['pick_station_id']);

        // Hàng nhẹ (mặc định) ⇒ service_type_id 2; không gửi tại điểm ⇒ drop field.
        $light = $connector->exposeBuildGhnPayload($base + ['goods_type' => 'light'], 0, $items);
        $this->assertSame(2, $light['service_type_id']);
        $this->assertArrayNotHasKey('pick_station_id', $light);

        // service tường minh (số) ưu tiên hơn goods_type.
        $explicit = $connector->exposeBuildGhnPayload($base + ['service' => '5', 'goods_type' => 'light'], 0, $items);
        $this->assertSame(5, $explicit['service_type_id']);
    }

    public function test_build_ghn_payload_allows_recipient_pays_override(): void
    {
        $connector = new class extends GhnConnector
        {
            public function exposeBuildGhnPayload(array $shipment, int $cod, array $items): array
            {
                return $this->buildGhnPayload($shipment, $cod, $items);
            }
        };

        $shipment = [
            'payment_type_id' => 2,
            'recipient' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000', 'address' => '123 Đường X'],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => '456 Đường Y'],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 50000,
        ];

        $payload = $connector->exposeBuildGhnPayload($shipment, 50000, [
            ['name' => 'Hàng', 'quantity' => 1, 'weight' => 200],
        ]);

        // Override rõ ràng ⇒ người nhận trả phí (2).
        $this->assertSame(2, $payload['payment_type_id']);
    }
}
