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

        // cod > 0 => recipient pays (payment_type_id 2), original COD-based default.
        $this->assertSame(2, $payload['payment_type_id']);
        $this->assertSame('KHONGCHOXEMHANG', $payload['required_note']);
        $this->assertArrayNotHasKey('note', $payload);
        $this->assertArrayNotHasKey('cod_failed_amount', $payload);
    }
}
