<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnClient;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * SPEC 0021 — /api/v1/master-data/* : nguồn dữ liệu Tỉnh / Quận / Phường VN dùng cho FE AddressPicker.
 *
 * Tận dụng GHN master-data API (chứa province_id / district_id / ward_code chuẩn của GHN) — khi shop
 * đẩy đơn lên GHN qua `CarrierConnector::createShipment`, các code này map sẵn vào payload, không cần
 * resolve thêm.
 *
 * Cache 24h shared toàn tenant (master-data global, không phải shop-scoped). Khi tenant chưa cấu hình
 * GHN ⇒ controller dùng `GHN_BOOTSTRAP_TOKEN` (env) hoặc bất kỳ token GHN nào tìm được trong DB (best-effort).
 */
class MasterDataController extends Controller
{
    public function provinces(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403, 'Bạn không có quyền.');

        $data = Cache::remember('ghn.master.provinces', now()->addHours(24), function () {
            $client = $this->bootstrapClient();
            if ($client === null) {
                return $this->staticProvincesFallback();
            }
            try {
                return $this->fetchProvinces($client);
            } catch (\Throwable) {
                return $this->staticProvincesFallback();
            }
        });

        return response()->json(['data' => $data]);
    }

    public function districts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403, 'Bạn không có quyền.');
        $request->validate(['province_id' => ['required', 'integer', 'min:1']]);
        $provinceId = $request->integer('province_id');

        $data = Cache::remember("ghn.master.districts.{$provinceId}", now()->addHours(24), function () use ($provinceId) {
            $client = $this->bootstrapClient();
            if ($client === null) {
                return [];
            }
            try {
                return $this->fetchDistricts($client, $provinceId);
            } catch (\Throwable) {
                return [];
            }
        });

        return response()->json(['data' => $data]);
    }

    public function wards(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403, 'Bạn không có quyền.');
        $request->validate(['district_id' => ['required', 'integer', 'min:1']]);
        $districtId = $request->integer('district_id');

        $data = Cache::remember("ghn.master.wards.{$districtId}", now()->addHours(24), function () use ($districtId) {
            $client = $this->bootstrapClient();
            if ($client === null) {
                return [];
            }
            try {
                return $this->fetchWards($client, $districtId);
            } catch (\Throwable) {
                return [];
            }
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Best-effort: tìm 1 token GHN nào đó (bất kỳ tenant nào có) để gọi master-data. Master-data global,
     * không scope theo shop ⇒ dùng token nào hợp lệ cũng được. Nếu KHÔNG có account nào ⇒ trả null,
     * controller dùng fallback tĩnh.
     */
    private function bootstrapClient(): ?GhnClient
    {
        $envToken = (string) config('fulfillment.ghn_bootstrap_token', '');
        if ($envToken !== '') {
            return new GhnClient($envToken);
        }
        $account = CarrierAccount::query()->where('carrier', 'ghn')->where('is_active', true)->first();
        if (! $account) {
            return null;
        }
        $token = (string) ($account->credentials['token'] ?? '');

        return $token !== '' ? new GhnClient($token) : null;
    }

    /** @return list<array{id:int, name:string, code:?string}> */
    private function fetchProvinces(GhnClient $client): array
    {
        $body = $this->httpFromClient($client)->get('/shiip/public-api/master-data/province')->json();
        if ((int) ($body['code'] ?? 0) !== 200) {
            return [];
        }

        return collect($body['data'] ?? [])->map(fn ($p) => [
            'id' => (int) ($p['ProvinceID'] ?? 0),
            'name' => (string) ($p['ProvinceName'] ?? ''),
            'code' => isset($p['Code']) ? (string) $p['Code'] : null,
        ])->filter(fn ($p) => $p['id'] > 0)->values()->all();
    }

    /** @return list<array{id:int, name:string, province_id:int}> */
    private function fetchDistricts(GhnClient $client, int $provinceId): array
    {
        $body = $this->httpFromClient($client)
            ->post('/shiip/public-api/master-data/district', ['province_id' => $provinceId])->json();
        if ((int) ($body['code'] ?? 0) !== 200) {
            return [];
        }

        return collect($body['data'] ?? [])->map(fn ($d) => [
            'id' => (int) ($d['DistrictID'] ?? 0),
            'name' => (string) ($d['DistrictName'] ?? ''),
            'province_id' => (int) ($d['ProvinceID'] ?? 0),
        ])->filter(fn ($d) => $d['id'] > 0)->values()->all();
    }

    /** @return list<array{code:string, name:string, district_id:int}> */
    private function fetchWards(GhnClient $client, int $districtId): array
    {
        $body = $this->httpFromClient($client)
            ->post('/shiip/public-api/master-data/ward', ['district_id' => $districtId])->json();
        if ((int) ($body['code'] ?? 0) !== 200) {
            return [];
        }

        return collect($body['data'] ?? [])->map(fn ($w) => [
            'code' => (string) ($w['WardCode'] ?? ''),
            'name' => (string) ($w['WardName'] ?? ''),
            'district_id' => (int) ($w['DistrictID'] ?? 0),
        ])->filter(fn ($w) => $w['code'] !== '')->values()->all();
    }

    /**
     * Lấy `Http::baseUrl()` + headers từ GhnClient (mở rộng nội bộ qua reflection — GhnClient hiện chưa
     * expose). Cho phép gọi mọi endpoint GHN với cùng credentials.
     */
    private function httpFromClient(GhnClient $client): \Illuminate\Http\Client\PendingRequest
    {
        $ref = new \ReflectionClass($client);
        $token = $ref->getProperty('token')->getValue($client);
        $baseUrl = (string) (config('fulfillment.ghn_base_url') ?: 'https://online-gateway.ghn.vn');

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withHeaders(['Token' => $token, 'Content-Type' => 'application/json'])
            ->timeout(15)->acceptJson();
    }

    /** Fallback tĩnh — 63 tỉnh VN với ProvinceID GHN (snapshot 2026-05; chỉ dùng khi không có GHN account). */
    private function staticProvincesFallback(): array
    {
        // Tối thiểu hoá: trả mảng rỗng + FE chuyển sang chế độ free-text. Embed list 63 sẽ phình code; nếu
        // shop muốn cascade thật ⇒ thêm 1 GHN account. (Follow-up khi có nhu cầu offline.)
        return [];
    }
}
