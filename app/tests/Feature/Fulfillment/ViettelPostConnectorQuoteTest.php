<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViettelPostConnectorQuoteTest extends TestCase
{
    private function account(): array
    {
        return [
            'id' => 1, 'carrier' => 'viettelpost',
            'credentials' => ['token' => 'VTP-TOKEN'],
            'default_service' => null,
            'meta' => [
                'from_address' => ['province_id' => 1, 'district_id' => 12, 'ward_id' => 49876],
                'defaults' => ['package' => ['weight_grams' => 1200]],
            ],
        ];
    }

    public function test_quote_uses_account_default_package_weight_and_returns_all_tiers(): void
    {
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            $url = $request->url();

            // Login VTP (Token header) — cần trước khi gọi getPriceAll (withToken mặc định true).
            if (str_contains($url, 'user/LoginVTP')) {
                return Http::response(['token' => 'test-vtp-token']);
            }

            // ViettelPostAddressResolver thử "mới" (v3, 2 cấp: Tỉnh + Phường) trước.
            if (str_contains($url, 'categories/listProvinceNew')) {
                return Http::response([
                    ['PROVINCE_ID' => 1, 'PROVINCE_NAME' => 'Hà Nội'],
                ]);
            }
            if (str_contains($url, 'categories/listWardsNew')) {
                return Http::response([
                    ['WARDS_ID' => 49876, 'WARDS_NAME' => 'Phường X', 'DISTRICT_ID' => 12],
                ]);
            }

            if (str_contains($url, 'order/getPriceAll')) {
                $captured = $request->data();

                return Http::response([
                    ['MA_DV_CHINH' => 'PHS', 'TEN_DICHVU' => 'Nội tỉnh tiết kiệm', 'GIA_CUOC' => 26400, 'THOI_GIAN' => '48 giờ'],
                    ['MA_DV_CHINH' => 'VCN', 'TEN_DICHVU' => 'Chuyển phát nhanh', 'GIA_CUOC' => 35000, 'THOI_GIAN' => '24 giờ'],
                ]);
            }

            return Http::response([]);
        });

        $result = (new ViettelPostConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường X'],
        ]);

        $this->assertSame(1200, $captured['PRODUCT_WEIGHT']);
        $this->assertCount(2, $result, 'Phải trả ĐỦ các gói VTP, không cắt còn 1.');
        $this->assertSame('Nội tỉnh tiết kiệm', $result[0]['name']);
        $this->assertSame('Chuyển phát nhanh', $result[1]['name']);
    }
}
