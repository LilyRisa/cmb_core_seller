<?php

namespace CMBcoreSeller\Integrations\Carriers\ViettelPost;

use Illuminate\Support\Facades\Cache;

/**
 * Map tên hành chính VN (Tỉnh/Quận/Phường — từ AddressPicker/đơn sàn) → ID nội bộ của Viettel Post,
 * dùng cho `createOrder` (địa chỉ ID). VTP createOrder chấp nhận cả địa chỉ "cũ" (3 cấp) lẫn "mới"
 * (2 cấp) — tài liệu báo lỗi ghi rõ "with address old or new".
 *
 * Chiến lược (KHÔNG trộn 2 không gian ID v2/v3):
 *   1. Thử "mới" (v3): /v3/categories/listProvinceNew + /v3/categories/listWardsNew (Tỉnh + Phường, 2 cấp).
 *   2. Fallback "cũ" (v2): /v2/categories/list{Province,District,Wards} (Tỉnh → Quận → Phường, 3 cấp).
 *
 * Match theo TÊN đã chuẩn hoá (lower + bỏ dấu + bỏ tiền tố cấp hành chính). Cache master-data 7 ngày.
 */
class ViettelPostAddressResolver
{
    private const CACHE_TTL = 7 * 24 * 60 * 60;

    public function __construct(private readonly ViettelPostClient $client) {}

    /**
     * @param  array{province?:?string, district?:?string, ward?:?string}  $address
     * @return array{province_id:?int, district_id:?int, ward_id:?int, format:string, matched:array{province:bool,ward:bool}}
     */
    public function resolve(array $address): array
    {
        $provinceName = $this->normalize($address['province'] ?? '');
        $districtName = $this->normalize($address['district'] ?? '');
        $wardName = $this->normalize($address['ward'] ?? '');

        // ---- 1) Đơn vị hành chính mới (v3, 2 cấp) ----
        $pNew = $this->findByName($this->provincesNew(), $provinceName, 'PROVINCE_NAME', 'WPROVINCE_NAME');
        if ($pNew !== null && $wardName !== '') {
            $pid = (int) $pNew['PROVINCE_ID'];
            $w = $this->findByName($this->wardsNew($pid), $wardName, 'WARDS_NAME');
            if ($w !== null) {
                return [
                    'province_id' => $pid,
                    'district_id' => isset($w['DISTRICT_ID']) ? (int) $w['DISTRICT_ID'] : null,
                    'ward_id' => (int) $w['WARDS_ID'],
                    'format' => 'new',
                    'matched' => ['province' => true, 'ward' => true],
                ];
            }
        }

        // ---- 2) Đơn vị hành chính cũ (v2, 3 cấp) ----
        $pOld = $this->findByName($this->provincesOld(), $provinceName, 'PROVINCE_NAME');
        $provinceId = $pOld ? (int) $pOld['PROVINCE_ID'] : null;

        $districtId = null;
        if ($provinceId !== null && $districtName !== '') {
            $d = $this->findByName($this->districtsOld($provinceId), $districtName, 'DISTRICT_NAME');
            $districtId = $d ? (int) $d['DISTRICT_ID'] : null;
        }

        $wardId = null;
        if ($wardName !== '') {
            $candidates = $districtId !== null
                ? [$districtId]
                : ($provinceId !== null ? array_map('intval', array_column($this->districtsOld($provinceId), 'DISTRICT_ID')) : []);
            foreach ($candidates as $dId) {
                $w = $this->findByName($this->wardsOld((int) $dId), $wardName, 'WARDS_NAME');
                if ($w !== null) {
                    $wardId = (int) $w['WARDS_ID'];
                    $districtId ??= (int) $dId;
                    break;
                }
            }
        }

        return [
            'province_id' => $provinceId,
            'district_id' => $districtId,
            'ward_id' => $wardId,
            'format' => 'old',
            'matched' => ['province' => $provinceId !== null, 'ward' => $wardId !== null],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function provincesNew(): array
    {
        return Cache::remember('vtp.master.provinces_new', self::CACHE_TTL, fn () => $this->safe(fn () => $this->client->listProvinceNew()));
    }

    /** @return list<array<string,mixed>> */
    private function wardsNew(int $provinceId): array
    {
        return Cache::remember("vtp.master.wards_new.{$provinceId}", self::CACHE_TTL, fn () => $this->safe(fn () => $this->client->listWardsNew($provinceId)));
    }

    /** @return list<array<string,mixed>> */
    private function provincesOld(): array
    {
        return Cache::remember('vtp.master.provinces_old', self::CACHE_TTL, fn () => $this->safe(fn () => $this->client->listProvince()));
    }

    /** @return list<array<string,mixed>> */
    private function districtsOld(int $provinceId): array
    {
        return Cache::remember("vtp.master.districts_old.{$provinceId}", self::CACHE_TTL, fn () => $this->safe(fn () => $this->client->listDistrict($provinceId)));
    }

    /** @return list<array<string,mixed>> */
    private function wardsOld(int $districtId): array
    {
        return Cache::remember("vtp.master.wards_old.{$districtId}", self::CACHE_TTL, fn () => $this->safe(fn () => $this->client->listWards($districtId)));
    }

    /**
     * @param  callable():array<int,mixed>  $fn
     * @return list<array<string,mixed>>
     */
    private function safe(callable $fn): array
    {
        try {
            return array_values((array) $fn());
        } catch (\Throwable) {
            return [];   // lỗi network → để connector validate báo lỗi rõ.
        }
    }

    /**
     * Tìm record theo tên đã chuẩn hoá; exact trước, fallback contains. Có thể dò nhiều key (typo VTP).
     *
     * @param  list<array<string,mixed>>  $list
     */
    private function findByName(array $list, string $needle, string ...$keys): ?array
    {
        if ($needle === '') {
            return null;
        }
        $contains = null;
        foreach ($list as $row) {
            foreach ($keys as $key) {
                if (! isset($row[$key])) {
                    continue;
                }
                $n = $this->normalize((string) $row[$key]);
                if ($n === $needle) {
                    return $row;   // exact match — ưu tiên tuyệt đối
                }
                if ($contains === null && $n !== '' && (str_contains($n, $needle) || str_contains($needle, $n))) {
                    $contains = $row;
                }
            }
        }

        return $contains;
    }

    /** Chuẩn hoá tên VN: lower, bỏ tiền tố cấp HC, bỏ dấu. (Đồng bộ với GhnAddressResolver.) */
    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = preg_replace('/^(thành phố |thanh pho |tỉnh |tinh |quận |quan |huyện |huyen |thị xã |thi xa |phường |phuong |xã |xa |thị trấn |thi tran |đặc khu |dac khu )/u', '', $s) ?? $s;
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
