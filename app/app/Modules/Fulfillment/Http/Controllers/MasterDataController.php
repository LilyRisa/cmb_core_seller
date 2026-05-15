<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Fulfillment\Models\AdminDistrict;
use CMBcoreSeller\Modules\Fulfillment\Models\AdminProvince;
use CMBcoreSeller\Modules\Fulfillment\Models\AdminWard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * SPEC 0021 — Endpoint master-data Tỉnh / Quận / Phường VN dùng cho FE AddressPicker.
 *
 * Đọc trực tiếp từ DB (bảng admin_provinces / admin_districts / admin_wards) — không gọi
 * external API mỗi request. DB được nạp qua `php artisan addresses:sync` (xem
 * {@see \CMBcoreSeller\Console\Commands\SyncVnAdminAddresses}).
 *
 * Hỗ trợ 2 format song song:
 *  - `format=new` — chuẩn 2-cấp (AddressKit cas.so, sau 2025): Tỉnh → Phường/Xã.
 *  - `format=old` — chuẩn 3-cấp (provinces.open-api.vn, pre-2025): Tỉnh → Quận → Phường/Xã.
 *
 * Cache 24h cho cả 2 format ⇒ tải đầu trang ~vài chục ms.
 */
class MasterDataController extends Controller
{
    private const TTL_SEC = 24 * 60 * 60;

    public function provinces(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403, 'Bạn không có quyền.');
        $format = $this->resolveFormat($request);

        $data = Cache::remember("master-data.provinces.{$format}", self::TTL_SEC, function () use ($format) {
            return AdminProvince::query()->where('format', $format)
                ->orderBy('sort_order')->orderBy('code')
                ->get(['code', 'name', 'english_name', 'division_type', 'phone_code', 'decree'])
                ->map(fn ($p) => [
                    'code' => $p->code, 'name' => $p->name,
                    'english_name' => $p->english_name,
                    'division_type' => $p->division_type,
                    'phone_code' => $p->phone_code,
                    'decree' => $p->decree,
                ])->all();
        });

        return response()->json(['data' => $data, 'meta' => ['format' => $format, 'count' => count($data)]]);
    }

    /**
     * Quận / Huyện — CHỈ áp dụng cho format='old' (3-cấp). Format='new' trả mảng rỗng để FE
     * skip cascade district khi user chọn địa chỉ mới.
     */
    public function districts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403, 'Bạn không có quyền.');
        $request->validate(['province_code' => ['required', 'string', 'max:16']]);
        $format = $this->resolveFormat($request);
        if ($format !== 'old') {
            return response()->json(['data' => [], 'meta' => ['format' => $format, 'note' => 'Chuẩn mới không có cấp quận/huyện.']]);
        }
        $provinceCode = (string) $request->query('province_code', '');
        $data = Cache::remember("master-data.districts.old.{$provinceCode}", self::TTL_SEC, function () use ($provinceCode) {
            return AdminDistrict::query()->where('province_code', $provinceCode)
                ->orderBy('code')
                ->get(['code', 'name', 'codename', 'division_type', 'province_code'])
                ->map(fn ($d) => [
                    'code' => $d->code, 'name' => $d->name,
                    'codename' => $d->codename,
                    'division_type' => $d->division_type,
                    'province_code' => $d->province_code,
                ])->all();
        });

        return response()->json(['data' => $data, 'meta' => ['format' => 'old', 'count' => count($data)]]);
    }

    /**
     * Phường / Xã / Đặc khu:
     *  - format='new': filter theo `province_code` (cấp con trực tiếp).
     *  - format='old': filter theo `district_code` (cấp con trực tiếp).
     */
    public function wards(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403, 'Bạn không có quyền.');
        $format = $this->resolveFormat($request);
        if ($format === 'new') {
            $request->validate(['province_code' => ['required', 'string', 'max:16']]);
            $provinceCode = (string) $request->query('province_code', '');
            $data = Cache::remember("master-data.wards.new.{$provinceCode}", self::TTL_SEC, function () use ($provinceCode) {
                return AdminWard::query()->where('format', 'new')->where('province_code', $provinceCode)
                    ->orderBy('name')
                    ->get(['code', 'name', 'english_name', 'division_type', 'province_code', 'decree'])
                    ->map(fn ($w) => [
                        'code' => $w->code, 'name' => $w->name,
                        'english_name' => $w->english_name,
                        'division_type' => $w->division_type,
                        'province_code' => $w->province_code,
                        'district_code' => null,
                        'decree' => $w->decree,
                    ])->all();
            });

            return response()->json(['data' => $data, 'meta' => ['format' => 'new', 'count' => count($data)]]);
        }
        // OLD — filter theo district_code.
        $request->validate(['district_code' => ['required', 'string', 'max:16']]);
        $districtCode = (string) $request->query('district_code', '');
        $data = Cache::remember("master-data.wards.old.{$districtCode}", self::TTL_SEC, function () use ($districtCode) {
            return AdminWard::query()->where('format', 'old')->where('district_code', $districtCode)
                ->orderBy('name')
                ->get(['code', 'name', 'codename', 'division_type', 'province_code', 'district_code'])
                ->map(fn ($w) => [
                    'code' => $w->code, 'name' => $w->name,
                    'codename' => $w->codename,
                    'division_type' => $w->division_type,
                    'province_code' => $w->province_code,
                    'district_code' => $w->district_code,
                ])->all();
        });

        return response()->json(['data' => $data, 'meta' => ['format' => 'old', 'count' => count($data)]]);
    }

    private function resolveFormat(Request $request): string
    {
        $f = strtolower((string) $request->query('format', 'new'));

        return in_array($f, ['new', 'old'], true) ? $f : 'new';
    }
}
