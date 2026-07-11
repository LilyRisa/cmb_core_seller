<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use PHPUnit\Framework\TestCase;

class GhtkConnectorDeliveryOptionsTest extends TestCase
{
    private function connector(): GhtkConnector
    {
        return new class extends GhtkConnector
        {
            public function exposeBuildGhtkPayload(array $shipment): array
            {
                return $this->buildGhtkPayload($shipment);
            }
        };
    }

    private function shipment(array $overrides = []): array
    {
        return array_merge([
            'client_order_code' => 'ORD-1',
            'recipient' => [
                'name' => 'Nguyen Van A',
                'phone' => '0900000000',
                'address' => '123 Le Loi',
                'province' => 'Ho Chi Minh',
                'district' => 'Quan 1',
            ],
            'sender' => [
                'name' => 'Shop A',
                'phone' => '0911111111',
                'address' => '456 Nguyen Trai',
                'province_name' => 'Ho Chi Minh',
                'district_name' => 'Quan 5',
            ],
            'parcel' => ['weight_grams' => 500],
            'cod_amount' => 100000,
        ], $overrides);
    }

    public function test_ghtk_has_no_failed_delivery_collect_capability(): void
    {
        $this->assertNotContains('failed_delivery_collect', (new GhtkConnector)->capabilities());
    }

    public function test_payload_sends_is_freeship_1_shop_pays(): void
    {
        // Phí ship là nội bộ (đã gộp vào COD) ⇒ is_freeship=1: shop trả cước, GHTK chỉ thu người nhận đúng
        // pick_money. Nếu KHÔNG gửi, GHTK mặc định 0 = thu pick_money + phí ship ⇒ thu chồng (docs api.ghtk.vn).
        $payload = $this->connector()->exposeBuildGhtkPayload($this->shipment());

        $this->assertSame(1, $payload['order']['is_freeship']);
    }

    public function test_allow_inspection_adds_tag_10_and_off_omits(): void
    {
        // Mặc định BẬT (thiếu cờ) ⇒ tag 10 "Cho xem hàng"; tắt ⇒ không gửi tag.
        $on = $this->connector()->exposeBuildGhtkPayload($this->shipment());
        $this->assertSame([10], $on['order']['tags']);

        $off = $this->connector()->exposeBuildGhtkPayload($this->shipment(['allow_inspection' => false]));
        $this->assertArrayNotHasKey('tags', $off['order']);
    }

    public function test_note_prefers_delivery_note_over_note_and_content(): void
    {
        $payload = $this->connector()->exposeBuildGhtkPayload($this->shipment([
            'delivery_note' => 'Giao giờ hành chính',
            'note' => 'Old note',
            'content' => 'Old content',
        ]));

        $this->assertSame('Giao giờ hành chính', $payload['order']['note']);
    }

    public function test_note_falls_back_to_note_then_content(): void
    {
        $connector = $this->connector();

        $payload = $connector->exposeBuildGhtkPayload($this->shipment(['note' => 'Legacy note']));
        $this->assertSame('Legacy note', $payload['order']['note']);

        $payload2 = $connector->exposeBuildGhtkPayload($this->shipment(['content' => 'Legacy content']));
        $this->assertSame('Legacy content', $payload2['order']['note']);
    }
}
