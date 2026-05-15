<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghn;

use Illuminate\Support\Facades\Cache;

/**
 * SPEC 0021 — resolve địa chỉ VN admin (đã chuẩn hoá từ `addresskit.cas.so` hoặc `provinces.open-api.vn`)
 * sang code của GHN.
 *
 * Lý do tồn tại: GHN dùng `ProvinceID` / `DistrictID` (integer) + `WardCode` (string) RIÊNG, KHÁC với
 * code của AddressKit/open-api. Khi user chọn địa chỉ qua AddressPicker (lưu code admin VN), trước khi
 * `GhnConnector::createShipment` chạy, ta phải map sang code GHN.
 *
 * Strategy: match theo TÊN (province name + district/ward name) — chuẩn hoá lower + bỏ dấu + bỏ tiền tố
 * "Tỉnh "/"Thành phố "/"Quận "/"Huyện "/"Phường "/"Xã ". Cache kết quả 7 ngày (TTL dài vì name VN ít đổi).
 *
 * Format 'new' (2-cấp) không có district ⇒ GHN không hỗ trợ trực tiếp. Workaround: pick district GHN từ
 * GHN ward record (mỗi ward GHN có `DistrictID` parent). Lookup ward bằng name, suy ra district.
 */
class GhnAddressResolver
{
    public function __construct(private readonly GhnClient $client) {}

    /**
     * Resolve `{province_name, district_name?, ward_name}` → `{province_id, district_id, ward_code}`.
     * Trả `null` cho field không tìm thấy (caller xử lý fallback / báo lỗi user).
     *
     * @param  array{province?:?string, district?:?string, ward?:?string}  $address
     * @return array{province_id:?int, district_id:?int, ward_code:?string, matched:array{province:bool,district:bool,ward:bool}}
     */
    public function resolve(array $address): array
    {
        $provinceName = $this->normalize($address['province'] ?? '');
        $districtName = $this->normalize($address['district'] ?? '');
        $wardName = $this->normalize($address['ward'] ?? '');

        $provinces = $this->ghnProvinces();
        $province = $this->findByName($provinces, $provinceName, 'ProvinceName');
        $provinceId = $province ? (int) $province['ProvinceID'] : null;

        $districtId = null;
        if ($provinceId !== null && $districtName !== '') {
            $districts = $this->ghnDistricts($provinceId);
            $district = $this->findByName($districts, $districtName, 'DistrictName');
            $districtId = $district ? (int) $district['DistrictID'] : null;
        }

        $wardCode = null;
        if ($wardName !== '') {
            // Có district_id → tra wards trong district. Không có (format='new') → loop districts trong province.
            $candidates = [];
            if ($districtId !== null) {
                $candidates = [$districtId];
            } elseif ($provinceId !== null) {
                $candidates = array_column($this->ghnDistricts($provinceId), 'DistrictID');
            }
            foreach ($candidates as $dId) {
                $wards = $this->ghnWards((int) $dId);
                $ward = $this->findByName($wards, $wardName, 'WardName');
                if ($ward !== null) {
                    $wardCode = (string) $ward['WardCode'];
                    if ($districtId === null) {
                        $districtId = (int) $dId;   // suy district từ ward (NEW format)
                    }
                    break;
                }
            }
        }

        return [
            'province_id' => $provinceId, 'district_id' => $districtId, 'ward_code' => $wardCode,
            'matched' => [
                'province' => $provinceId !== null,
                'district' => $districtId !== null,
                'ward' => $wardCode !== null,
            ],
        ];
    }

    /** Cache GHN master-data 7 ngày — name VN ít đổi, code lại càng ít đổi. */
    private const CACHE_TTL = 7 * 24 * 60 * 60;

    /** @return list<array<string,mixed>> */
    private function ghnProvinces(): array
    {
        return Cache::remember('ghn.master.provinces.raw', self::CACHE_TTL, function () {
            $body = $this->client->getProvinces();

            return (int) ($body['code'] ?? 0) === 200 ? (array) ($body['data'] ?? []) : [];
        });
    }

    /** @return list<array<string,mixed>> */
    private function ghnDistricts(int $provinceId): array
    {
        return Cache::remember("ghn.master.districts.{$provinceId}.raw", self::CACHE_TTL, function () use ($provinceId) {
            try {
                $body = $this->httpFromClient()->post('/shiip/public-api/master-data/district', ['province_id' => $provinceId])->json();

                return (int) ($body['code'] ?? 0) === 200 ? (array) ($body['data'] ?? []) : [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** @return list<array<string,mixed>> */
    private function ghnWards(int $districtId): array
    {
        return Cache::remember("ghn.master.wards.{$districtId}.raw", self::CACHE_TTL, function () use ($districtId) {
            try {
                $body = $this->httpFromClient()->post('/shiip/public-api/master-data/ward', ['district_id' => $districtId])->json();

                return (int) ($body['code'] ?? 0) === 200 ? (array) ($body['data'] ?? []) : [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    private function httpFromClient(): \Illuminate\Http\Client\PendingRequest
    {
        // GhnClient::http() là private; reuse logic qua getProvinces (đã expose).
        $ref = new \ReflectionMethod($this->client, 'http');
        $ref->setAccessible(true);

        return $ref->invoke($this->client, false);
    }

    /**
     * Tìm record matching theo tên đã chuẩn hoá. Match exact trước, fallback contains.
     *
     * @param  list<array<string,mixed>>  $list
     */
    private function findByName(array $list, string $needle, string $key): ?array
    {
        if ($needle === '') {
            return null;
        }
        $exact = null;
        $contains = null;
        foreach ($list as $row) {
            $n = $this->normalize((string) ($row[$key] ?? ''));
            if ($n === $needle) {
                $exact = $row;
                break;
            }
            if ($contains === null && (str_contains($n, $needle) || str_contains($needle, $n))) {
                $contains = $row;
            }
        }

        return $exact ?? $contains;
    }

    /**
     * Chuẩn hoá tên VN cho match: lower-case, bỏ dấu Việt, bỏ tiền tố cấp hành chính.
     * Vd "Quận Bình Thạnh" → "binh thanh", "Tỉnh Hà Giang" → "ha giang".
     */
    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        // bỏ tiền tố cấp hành chính (cả new + old)
        $s = preg_replace('/^(thành phố |thanh pho |tỉnh |tinh |quận |quan |huyện |huyen |thị xã |thi xa |phường |phuong |xã |xa |thị trấn |thi tran |đặc khu |dac khu )/u', '', $s) ?? $s;
        // bỏ dấu tiếng Việt
        $accents = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ',
        ];
        $plain = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y', 'd',
        ];
        $s = str_replace($accents, $plain, $s);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return trim($s);
    }
}
