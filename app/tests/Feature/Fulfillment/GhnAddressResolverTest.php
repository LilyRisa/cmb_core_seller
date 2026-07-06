<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnAddressResolver;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0021 — resolve địa chỉ VN → mã GHN, KỂ CẢ địa chỉ "mới" (2 cấp sau sáp nhập 2025).
 *
 * GHN vẫn tạo đơn bằng v2 (district_id + ward_code CŨ). Phường của tỉnh mới sáp nhập
 * nằm dưới TỈNH CŨ trong dữ liệu GHN v2 (vd "Xã Thịnh Minh" thuộc Phú Thọ mới → GHN v2
 * đặt dưới tỉnh "Hòa Bình"). Resolver phải suy được qua crosswalk 34→63.
 */
class GhnAddressResolverTest extends TestCase
{
    /** GHN v2 master-data giả lập: 2 tỉnh (Phú Thọ mới giữ tên, Hòa Bình cũ đã gộp vào Phú Thọ). */
    private function fakeGhnMasterData(): void
    {
        Cache::flush();
        Http::fake(function ($request) {
            $url = $request->url();
            $body = json_decode($request->body() ?: '{}', true) ?: [];

            if (str_contains($url, 'master-data/province')) {
                return Http::response(['code' => 200, 'data' => [
                    ['ProvinceID' => 229, 'ProvinceName' => 'Phú Thọ', 'NameExtension' => ['Phú Thọ', 'Tỉnh Phú Thọ']],
                    ['ProvinceID' => 267, 'ProvinceName' => 'Hòa Bình', 'NameExtension' => ['Hòa Bình', 'Tỉnh Hòa Bình']],
                ]]);
            }
            if (str_contains($url, 'master-data/district')) {
                $pid = (int) ($body['province_id'] ?? 0);
                $data = $pid === 267
                    ? [['DistrictID' => 1678, 'DistrictName' => 'Thành phố Hòa Bình']]
                    : [['DistrictID' => 1900, 'DistrictName' => 'Thành phố Việt Trì']]; // Phú Thọ cũ — không chứa ward

                return Http::response(['code' => 200, 'data' => $data]);
            }
            if (str_contains($url, 'master-data/ward')) {
                $did = (int) ($body['district_id'] ?? 0);
                $data = $did === 1678
                    ? [['WardCode' => '800261', 'DistrictID' => 1678, 'WardName' => 'Xã Thịnh Minh']]
                    : [['WardCode' => '999999', 'DistrictID' => 1900, 'WardName' => 'Phường Gia Cẩm']];

                return Http::response(['code' => 200, 'data' => $data]);
            }

            return Http::response(['code' => 200, 'data' => []]);
        });
    }

    private function resolver(): GhnAddressResolver
    {
        return new GhnAddressResolver(new GhnClient('TEST-TOKEN', 9999));
    }

    public function test_new_two_level_address_in_merged_province_resolves_via_old_province(): void
    {
        $this->fakeGhnMasterData();

        // Địa chỉ MỚI 2 cấp: tỉnh "Phú Thọ" (mới), phường "Xã Thịnh Minh", KHÔNG có quận.
        $res = $this->resolver()->resolve([
            'province' => 'Phú Thọ',
            'district' => null,
            'ward' => 'Xã Thịnh Minh',
        ]);

        $this->assertSame(1678, $res['district_id'], 'Phải suy được district cũ (TP Hòa Bình) từ ward.');
        $this->assertSame('800261', $res['ward_code']);
        $this->assertTrue($res['matched']['district']);
        $this->assertTrue($res['matched']['ward']);
    }

    public function test_transient_error_is_not_cached_and_self_heals(): void
    {
        // Lần 1 tỉnh trả 401 (token sai) ⇒ rỗng; lần 2 trả OK. Cache rỗng KHÔNG được lưu,
        // nên lần 2 vẫn resolve được (không kẹt 7 ngày như bug đã gặp trên prod).
        Cache::flush();
        $provinceCalls = 0;
        Http::fake(function ($request) use (&$provinceCalls) {
            $url = $request->url();
            if (str_contains($url, 'master-data/province')) {
                $provinceCalls++;

                return $provinceCalls === 1
                    ? Http::response(['code' => 401, 'message' => 'Token is not valid!'], 401)
                    : Http::response(['code' => 200, 'data' => [['ProvinceID' => 267, 'ProvinceName' => 'Hòa Bình']]]);
            }
            if (str_contains($url, 'master-data/district')) {
                return Http::response(['code' => 200, 'data' => [['DistrictID' => 1678, 'DistrictName' => 'Thành phố Hòa Bình']]]);
            }
            if (str_contains($url, 'master-data/ward')) {
                return Http::response(['code' => 200, 'data' => [['WardCode' => '230110', 'DistrictID' => 1678, 'WardName' => 'Phường Hòa Bình']]]);
            }

            return Http::response(['code' => 200, 'data' => []]);
        });

        $addr = ['province' => 'Hòa Bình', 'district' => 'Thành phố Hòa Bình', 'ward' => 'Phường Hòa Bình'];

        $first = $this->resolver()->resolve($addr);
        $this->assertNull($first['ward_code'], 'Lần 1 lỗi token ⇒ chưa resolve.');

        $second = $this->resolver()->resolve($addr);
        $this->assertSame('230110', $second['ward_code'], 'Lần 2 phải resolve — cache không bị đầu độc bởi rỗng.');
    }

    public function test_direct_address_still_resolves_without_fallback(): void
    {
        $this->fakeGhnMasterData();

        // Ward nằm ngay trong tỉnh khớp tên (Hòa Bình) — path thường, không cần crosswalk.
        $res = $this->resolver()->resolve([
            'province' => 'Hòa Bình',
            'district' => 'Thành phố Hòa Bình',
            'ward' => 'Xã Thịnh Minh',
        ]);

        $this->assertSame(267, $res['province_id']);
        $this->assertSame(1678, $res['district_id']);
        $this->assertSame('800261', $res['ward_code']);
    }
}
