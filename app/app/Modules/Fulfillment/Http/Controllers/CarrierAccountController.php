<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnClient;
use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostClient;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\CarrierAccountResource;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** /api/v1/carrier-accounts + /api/v1/carriers — tenant ĐVVC credentials. See SPEC 0006 §6. */
class CarrierAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền xem ĐVVC.');

        return response()->json(['data' => CarrierAccountResource::collection(CarrierAccount::query()->orderByDesc('is_default')->orderBy('id')->get())]);
    }

    /** GET /api/v1/carriers — carrier codes available in this deployment. */
    public function carriers(Request $request, CarrierRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền.');
        $out = [];
        foreach ($registry->carriers() as $code) {
            $c = $registry->for($code);
            $out[] = [
                'code' => $code,
                'name' => $c->displayName(),
                'capabilities' => $c instanceof AbstractCarrierConnector ? $c->capabilities() : [],
                'needs_credentials' => $code !== 'manual',
            ];
        }

        return response()->json(['data' => $out]);
    }

    public function store(Request $request, CarrierRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_service' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_default' => ['sometimes', 'boolean'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ]);
        abort_unless($registry->has($data['carrier']), 422, 'ĐVVC không được hỗ trợ.');
        $account = DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                CarrierAccount::query()->update(['is_default' => false]);
            }

            return CarrierAccount::query()->create([   // tenant_id auto-filled by BelongsToTenant
                'carrier' => $data['carrier'], 'name' => $data['name'],
                'credentials' => $data['credentials'] ?? [], 'default_service' => $data['default_service'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false), 'is_active' => true, 'meta' => $data['meta'] ?? null,
            ]);
        });

        // A2 — Auto-verify credentials sau khi tạo. Nếu lỗi ⇒ vẫn lưu nhưng `is_active=false` + cờ meta.
        $this->runVerifyAndPersist($registry, $account);

        return response()->json(['data' => new CarrierAccountResource($account->refresh())], 201);
    }

    /**
     * POST /api/v1/carrier-accounts/{id}/verify — gọi connector kiểm tra credentials sống. Trả trạng thái
     * verify (ok/lỗi/expired) + cập nhật `meta.last_verified_at` + `meta.last_verify_error`. KHÔNG tự bật
     * is_active = false để tránh ngắt vận đơn đang xử lý — chỉ surface cho user quyết định.
     */
    public function verify(Request $request, int $id, CarrierRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền.');
        $account = CarrierAccount::query()->findOrFail($id);
        $result = $this->runVerifyAndPersist($registry, $account, autoToggleActive: false);

        return response()->json(['data' => [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'error_code' => $result['error_code'] ?? null,
            'expires_at' => $result['expires_at'] ?? null,
            'verified_at' => now()->toIso8601String(),
            'account' => new CarrierAccountResource($account->refresh()),
        ]]);
    }

    /**
     * @return array{ok:bool, message:string, expires_at?:?string, error_code?:string}
     */
    private function runVerifyAndPersist(CarrierRegistry $registry, CarrierAccount $account, bool $autoToggleActive = true): array
    {
        if (! $registry->has($account->carrier)) {
            return ['ok' => false, 'message' => 'ĐVVC chưa được đăng ký trong hệ thống.', 'error_code' => 'unregistered', 'expires_at' => null];
        }
        $connector = $registry->for($account->carrier);
        try {
            $result = $connector->verifyCredentials($account->toConnectorArray());
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'Lỗi kiểm tra: '.$e->getMessage(), 'error_code' => 'network', 'expires_at' => null];
        }
        $meta = (array) ($account->meta ?? []);
        $meta['last_verified_at'] = now()->toIso8601String();
        $meta['last_verify_ok'] = (bool) $result['ok'];
        $meta['last_verify_error'] = $result['ok'] ? null : ($result['message'] ?? null);
        if (! empty($result['expires_at'])) {
            $meta['credentials_expires_at'] = $result['expires_at'];
        }
        $patch = ['meta' => $meta];
        if ($autoToggleActive && ! $result['ok'] && ($result['error_code'] ?? '') === 'invalid_credentials') {
            // Credentials sai từ đầu (lúc tạo) ⇒ tự tắt is_active để tránh tạo vận đơn lỗi.
            $patch['is_active'] = false;
        }
        $account->forceFill($patch)->save();

        return $result;
    }

    public function update(Request $request, int $id, CarrierRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        $account = CarrierAccount::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_service' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ]);
        // Sửa từng phần: MERGE credentials/meta thay vì ghi đè — để trống credential ⇒ giữ giá trị cũ
        // (FE không gửi lại secret); cập nhật `from_address` không làm mất `meta.last_verify_*`.
        $credChanged = false;
        DB::transaction(function () use ($account, &$data, &$credChanged) {
            if (array_key_exists('is_default', $data) && $data['is_default']) {
                CarrierAccount::query()->where('id', '!=', $account->getKey())->update(['is_default' => false]);
            }
            if (array_key_exists('credentials', $data)) {
                $incoming = array_filter((array) ($data['credentials'] ?? []), fn ($v) => $v !== null && $v !== '');
                $credChanged = $incoming !== [];
                $data['credentials'] = array_merge((array) ($account->credentials ?? []), $incoming);
            }
            if (array_key_exists('meta', $data)) {
                $data['meta'] = array_merge((array) ($account->meta ?? []), (array) ($data['meta'] ?? []));
            }
            $account->forceFill($data)->save();
        });

        // Credentials đổi ⇒ verify lại để cập nhật trạng thái kết nối (giống lúc tạo).
        if ($credChanged) {
            $this->runVerifyAndPersist($registry, $account->refresh());
        }

        return response()->json(['data' => new CarrierAccountResource($account->refresh())]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        CarrierAccount::query()->findOrFail($id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * POST /api/v1/carrier-accounts/ghn/shops — liệt kê shop gắn với token GHN. Dùng trong form "Thêm
     * tài khoản GHN" cho phép user chọn 1 trong nhiều gian hàng thay vì gõ ShopId tay. KHÔNG yêu cầu
     * CarrierAccount đã lưu. Cache 10 phút theo hash token (giảm hit GHN; shop list ít thay đổi).
     *
     * Payload: { token: string }
     */
    public function ghnShops(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        $data = $request->validate([
            'token' => ['required', 'string', 'max:200'],
        ]);

        $tokenHash = substr(hash('sha256', $data['token']), 0, 16);
        $cacheKey = "ghn.fe.{$tokenHash}.shops";

        try {
            $body = Cache::remember($cacheKey, 600, function () use ($data) {
                $client = new GhnClient($data['token']);

                return $client->getShops();
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Không gọi được GHN: '.$e->getMessage(),
                'errors' => ['token' => ['Token có thể không hợp lệ hoặc GHN không phản hồi.']],
            ], 422);
        }

        $code = (int) ($body['code'] ?? 0);
        if ($code !== 200) {
            return response()->json([
                'message' => $body['message'] ?? 'GHN trả mã lỗi '.$code,
                'errors' => ['token' => [$body['message'] ?? 'Token GHN không hợp lệ.']],
            ], 422);
        }

        // GHN trả `data.shops[]` hoặc `data` là array — chuẩn hoá về list.
        $shopsRaw = $body['data']['shops'] ?? $body['data'] ?? [];
        $shops = array_values(array_map(function ($s) {
            return [
                'id' => (int) ($s['_id'] ?? $s['id'] ?? 0),
                'name' => (string) ($s['name'] ?? ('Shop #'.($s['_id'] ?? '?'))),
                'phone' => (string) ($s['phone'] ?? ''),
                'address' => (string) ($s['address'] ?? ''),
                'district_id' => isset($s['district_id']) ? (int) $s['district_id'] : null,
                'ward_code' => isset($s['ward_code']) ? (string) $s['ward_code'] : null,
                'version' => isset($s['version']) ? (int) $s['version'] : null,
                'status' => isset($s['status']) ? (int) $s['status'] : null,
            ];
        }, (array) $shopsRaw));

        return response()->json(['data' => $shops]);
    }

    /**
     * POST /api/v1/carrier-accounts/ghn/master-data — proxy GHN master-data (province/district/ward)
     * lấy bằng token user đang nhập trong form "Thêm tài khoản". KHÔNG yêu cầu CarrierAccount đã lưu —
     * dùng để user xem trước/chọn mã quận trước khi submit. Cache theo hash token để giảm hit GHN.
     *
     * Payload: { token: string, level: 'provinces'|'districts'|'wards', province_id?: int, district_id?: int }
     */
    public function ghnMasterData(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        $data = $request->validate([
            'token' => ['required', 'string', 'max:200'],
            'level' => ['required', 'string', 'in:provinces,districts,wards'],
            'province_id' => ['required_if:level,districts', 'integer'],
            'district_id' => ['required_if:level,wards', 'integer'],
        ]);

        // Cache 1 tiếng theo (token-hash + level + parent_id) — name VN ít đổi; sai token sẽ surface ngay.
        $tokenHash = substr(hash('sha256', $data['token']), 0, 16);
        $cacheKey = match ($data['level']) {
            'provinces' => "ghn.fe.{$tokenHash}.provinces",
            'districts' => "ghn.fe.{$tokenHash}.districts.{$data['province_id']}",
            'wards' => "ghn.fe.{$tokenHash}.wards.{$data['district_id']}",
        };

        try {
            $body = Cache::remember($cacheKey, 3600, function () use ($data) {
                $client = new GhnClient($data['token']);

                return match ($data['level']) {
                    'provinces' => $client->getProvinces(),
                    'districts' => $client->getDistricts((int) $data['province_id']),
                    'wards' => $client->getWards((int) $data['district_id']),
                };
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Không gọi được GHN: '.$e->getMessage(),
                'errors' => ['token' => ['Token có thể không hợp lệ hoặc GHN không phản hồi.']],
            ], 422);
        }

        $code = (int) ($body['code'] ?? 0);
        if ($code !== 200) {
            return response()->json([
                'message' => $body['message'] ?? 'GHN trả mã lỗi '.$code,
                'errors' => ['token' => [$body['message'] ?? 'Token GHN không hợp lệ.']],
            ], 422);
        }

        return response()->json(['data' => array_values((array) ($body['data'] ?? []))]);
    }

    /**
     * POST /api/v1/carrier-accounts/viettelpost/master-data — proxy danh mục Tỉnh/Phường (đơn vị HC mới v3)
     * của Viettel Post cho form chọn "địa chỉ kho hàng" (cascading). Danh mục VTP công khai (không cần token)
     * ⇒ không nhận credentials. Cache 1 tiếng. SPEC 0034.
     *
     * Payload: { level: 'provinces'|'wards', province_id?: int }
     */
    public function viettelpostMasterData(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        $data = $request->validate([
            'level' => ['required', 'string', 'in:provinces,wards'],
            'province_id' => ['required_if:level,wards', 'integer'],
        ]);

        $cacheKey = $data['level'] === 'provinces'
            ? 'vtp.fe.provinces_new'
            : "vtp.fe.wards_new.{$data['province_id']}";

        try {
            $rows = Cache::remember($cacheKey, 3600, function () use ($data) {
                $client = new ViettelPostClient([]);   // danh mục không cần token

                return $data['level'] === 'provinces'
                    ? $client->listProvinceNew()
                    : $client->listWardsNew((int) $data['province_id']);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Không gọi được Viettel Post: '.$e->getMessage(),
                'errors' => ['level' => ['Viettel Post không phản hồi danh mục địa danh.']],
            ], 422);
        }

        return response()->json(['data' => $rows]);
    }
}
