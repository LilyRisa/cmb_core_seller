<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GhnConnectorQuoteTest extends TestCase
{
    private function fakeMasterDataAndFee(int $fee = 28000, int $insuranceFee = 0): void
    {
        Cache::flush();
        Http::fake(function ($request) use ($fee, $insuranceFee) {
            $url = $request->url();
            if (str_contains($url, 'master-data/province')) {
                return Http::response(['code' => 200, 'data' => [
                    ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội', 'NameExtension' => ['Hà Nội']],
                ]]);
            }
            if (str_contains($url, 'master-data/district')) {
                return Http::response(['code' => 200, 'data' => [
                    ['DistrictID' => 1442, 'DistrictName' => 'Quận Cầu Giấy'],
                ]]);
            }
            if (str_contains($url, 'master-data/ward')) {
                return Http::response(['code' => 200, 'data' => [
                    ['WardCode' => '21211', 'DistrictID' => 1442, 'WardName' => 'Phường Dịch Vọng'],
                ]]);
            }
            if (str_contains($url, 'shipping-order/fee')) {
                return Http::response(['code' => 200, 'message' => 'Success', 'data' => [
                    'total' => $fee, 'service_fee' => $fee, 'insurance_fee' => $insuranceFee,
                ]]);
            }

            return Http::response(['code' => 200, 'data' => []]);
        });
    }

    private function account(): array
    {
        return [
            'id' => 1, 'carrier' => 'ghn',
            'credentials' => ['token' => 'TEST-TOKEN', 'shop_id' => 9999],
            'default_service' => null,
            'meta' => [
                'from_address' => ['name' => 'Shop', 'phone' => '0900000000', 'address' => 'Số 1', 'district_id' => 1442, 'ward_code' => '21211'],
                'defaults' => ['goods_type' => 'light', 'package' => ['weight_grams' => 500, 'length_cm' => 15, 'width_cm' => 15, 'height_cm' => 10]],
            ],
        ];
    }

    public function test_quote_returns_single_fee_for_light_goods(): void
    {
        $this->fakeMasterDataAndFee(fee: 28000, insuranceFee: 0);

        $result = (new GhnConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường Dịch Vọng'],
        ]);

        $this->assertCount(1, $result);
        $this->assertSame('ghn', $result[0]['carrier']);
        $this->assertSame(28000, $result[0]['fee']);
        $this->assertSame(0, $result[0]['insurance_fee']);
        $this->assertNull($result[0]['name']);
    }

    public function test_quote_uses_service_type_id_5_for_heavy_goods(): void
    {
        $this->fakeMasterDataAndFee(fee: 40000);
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            if (str_contains($request->url(), 'shipping-order/fee')) {
                $captured = json_decode($request->body(), true);

                return Http::response(['code' => 200, 'data' => ['total' => 40000, 'insurance_fee' => 0]]);
            }
            if (str_contains($request->url(), 'master-data/province')) {
                return Http::response(['code' => 200, 'data' => [['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội']]]);
            }
            if (str_contains($request->url(), 'master-data/district')) {
                return Http::response(['code' => 200, 'data' => [['DistrictID' => 1442, 'DistrictName' => 'Quận Cầu Giấy']]]);
            }
            if (str_contains($request->url(), 'master-data/ward')) {
                return Http::response(['code' => 200, 'data' => [['WardCode' => '21211', 'DistrictID' => 1442, 'WardName' => 'Phường Dịch Vọng']]]);
            }

            return Http::response(['code' => 200, 'data' => []]);
        });

        $account = $this->account();
        $account['meta']['defaults']['goods_type'] = 'heavy';
        (new GhnConnector)->quote($account, [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường Dịch Vọng'],
        ]);

        $this->assertSame(5, $captured['service_type_id']);
    }

    public function test_quote_returns_empty_when_recipient_address_unresolvable(): void
    {
        Cache::flush();
        Http::fake(fn () => Http::response(['code' => 200, 'data' => []]));

        $result = (new GhnConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Không Tồn Tại', 'district' => 'X', 'ward' => 'Y'],
        ]);

        $this->assertSame([], $result);
    }
}
