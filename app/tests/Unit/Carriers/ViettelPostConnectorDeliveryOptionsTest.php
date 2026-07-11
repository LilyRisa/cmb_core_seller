<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostConnector;
use PHPUnit\Framework\TestCase;

class ViettelPostConnectorDeliveryOptionsTest extends TestCase
{
    public function test_capability_includes_failed_delivery_collect(): void
    {
        $c = new ViettelPostConnector;
        $this->assertContains('failed_delivery_collect', $c->capabilities());
    }

    public function test_build_vtp_payload_maps_delivery_note_and_extra_money(): void
    {
        $connector = new class extends ViettelPostConnector
        {
            public function exposeBuildVtpPayload(array $shipment, int $cod, array $listItem, int $totalQty, int $totalValue, int $weight, string $service): array
            {
                return $this->buildVtpPayload($shipment, $cod, $listItem, $totalQty, $totalValue, $weight, $service);
            }
        };

        $shipment = [
            'client_order_code' => 'ORD-1',
            'delivery_note' => 'Gọi trước khi giao',
            'failed_collect_amount' => 30000,
            'recipient' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000', 'address' => '123 Đường X', 'province_id' => 1, 'ward_id' => 1],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => '456 Đường Y', 'province_id' => 1, 'ward_id' => 1],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 50000,
        ];

        $payload = $connector->exposeBuildVtpPayload($shipment, 50000, [], 1, 50000, 500, 'SVC1');

        // Có COD ⇒ ORDER_PAYMENT=3 = thu hộ tiền hàng, KHÔNG thu cước (shop trả cước) — docs partner.viettelpost.vn.
        $this->assertSame(3, $payload['ORDER_PAYMENT']);
        // Thiếu required_note/allow_inspection ⇒ mặc định an toàn CHOXEMHANGKHONGTHU (cho xem, không thử).
        $this->assertSame('Cho khách xem hàng, không cho thử. Gọi trước khi giao', $payload['ORDER_NOTE']);
        $this->assertSame(30000, $payload['EXTRA_MONEY']);
        $this->assertSame(['XMG'], $payload['LIST_ITEM_EXTRA']);
    }

    public function test_order_note_states_no_inspection_when_allow_inspection_off(): void
    {
        $connector = new class extends ViettelPostConnector
        {
            public function exposeBuildVtpPayload(array $shipment, int $cod, array $listItem, int $totalQty, int $totalValue, int $weight, string $service): array
            {
                return $this->buildVtpPayload($shipment, $cod, $listItem, $totalQty, $totalValue, $weight, $service);
            }
        };

        $shipment = [
            'client_order_code' => 'ORD-4',
            'delivery_note' => 'Gọi trước khi giao',
            'allow_inspection' => false,
            'recipient' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000', 'address' => '123 Đường X', 'province_id' => 1, 'ward_id' => 1],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => '456 Đường Y', 'province_id' => 1, 'ward_id' => 1],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 50000,
        ];

        $payload = $connector->exposeBuildVtpPayload($shipment, 50000, [], 1, 50000, 500, 'SVC1');

        // allow_inspection=false ⇒ KHONGCHOXEMHANG ⇒ ghi chú nêu rõ "Không cho khách xem hàng" (VTP không có field riêng).
        $this->assertSame('Không cho khách xem hàng. Gọi trước khi giao', $payload['ORDER_NOTE']);
    }

    public function test_build_vtp_payload_clamps_extra_money_to_2x_fee_estimate_when_available(): void
    {
        $connector = new class extends ViettelPostConnector
        {
            public function exposeBuildVtpPayload(array $shipment, int $cod, array $listItem, int $totalQty, int $totalValue, int $weight, string $service): array
            {
                return $this->buildVtpPayload($shipment, $cod, $listItem, $totalQty, $totalValue, $weight, $service);
            }
        };

        $shipment = [
            'client_order_code' => 'ORD-2',
            'failed_collect_amount' => 100000,
            'fee' => 20000,
            'recipient' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000', 'address' => '123 Đường X', 'province_id' => 1, 'ward_id' => 1],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => '456 Đường Y', 'province_id' => 1, 'ward_id' => 1],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 0,
        ];

        $payload = $connector->exposeBuildVtpPayload($shipment, 0, [], 1, 0, 500, 'SVC1');

        // 2 * fee(20000) = 40000, clamp min(100000, 40000) = 40000.
        $this->assertSame(40000, $payload['EXTRA_MONEY']);
        $this->assertSame(['XMG'], $payload['LIST_ITEM_EXTRA']);
    }

    public function test_build_vtp_payload_omits_extra_money_when_failed_collect_amount_zero(): void
    {
        $connector = new class extends ViettelPostConnector
        {
            public function exposeBuildVtpPayload(array $shipment, int $cod, array $listItem, int $totalQty, int $totalValue, int $weight, string $service): array
            {
                return $this->buildVtpPayload($shipment, $cod, $listItem, $totalQty, $totalValue, $weight, $service);
            }
        };

        $shipment = [
            'client_order_code' => 'ORD-3',
            'recipient' => ['name' => 'Nguyễn Văn A', 'phone' => '0900000000', 'address' => '123 Đường X', 'province_id' => 1, 'ward_id' => 1],
            'sender' => ['name' => 'Shop', 'phone' => '0911111111', 'address' => '456 Đường Y', 'province_id' => 1, 'ward_id' => 1],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 0,
        ];

        $payload = $connector->exposeBuildVtpPayload($shipment, 0, [], 1, 0, 500, 'SVC1');

        $this->assertArrayNotHasKey('EXTRA_MONEY', $payload);
        $this->assertArrayNotHasKey('LIST_ITEM_EXTRA', $payload);
    }
}
