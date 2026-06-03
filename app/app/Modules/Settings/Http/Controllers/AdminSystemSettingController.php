<?php

namespace CMBcoreSeller\Modules\Settings\Http\Controllers;

use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Settings\Support\SystemSettingsCatalog;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Spec 2026-05-17 — `/api/v1/admin/system-settings/*`.
 *
 * GET     /                      → list theo group (catalog + DB merge); hiển thị value
 *                                  KHÔNG che (kể cả secret) để super-admin đối chiếu/sửa.
 * GET     /{key}/reveal          → trả plain (audit `admin.setting.reveal`).
 * PATCH   /{key}                 → cập nhật value (validate type).
 * DELETE  /{key}                 → xoá row → fallback env.
 * POST    /sync-from-env         → seed các key chưa có row từ env.
 *
 * Mọi mutation ghi audit qua listener `LogSystemSettingChanged`. Reveal có
 * audit riêng để có dấu vết "ai đã đọc plain secret".
 */
class AdminSystemSettingController extends Controller
{
    public function __construct(private readonly SystemSettingService $svc) {}

    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');
        $catalog = collect(SystemSettingsCatalog::all());
        if ($group) {
            $catalog = $catalog->where('group', $group);
        }

        $persisted = SystemSetting::query()
            ->whereIn('key', $catalog->keys())
            ->get()
            ->keyBy('key');

        $data = $catalog->map(function (array $meta, string $key) use ($persisted) {
            $row = $persisted->get($key);
            $value = null;

            if ($row !== null) {
                // Chủ dự án yêu cầu admin hiển thị MỌI giá trị (kể cả secret) không che để
                // tiện đối chiếu/sửa cấu hình. Trang này chỉ super-admin (auth:admin_web) truy
                // cập; reveal endpoint + audit vẫn giữ cho truy vết khi cần. Service cast đúng type.
                $value = $this->svc->get($key);
            }

            return [
                'key' => $key,
                'group' => $meta['group'],
                'type' => $meta['type'],
                'is_secret' => $meta['is_secret'],
                'label' => $meta['label'],
                'description' => $meta['description'] ?? null,
                'env_fallback' => env($meta['env']),
                'value' => $value,
                'updated_at' => $row?->updated_at?->toIso8601String(),
                'updated_by_admin_id' => $row?->updated_by_admin_id,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    public function reveal(string $key): JsonResponse
    {
        if (! SystemSettingsCatalog::has($key)) {
            return $this->keyNotAllowed();
        }
        $value = $this->svc->get($key);
        AuditLog::record('admin.setting.reveal', null, ['key' => $key]);

        return response()->json(['data' => ['key' => $key, 'value' => $value]]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        if (! SystemSettingsCatalog::has($key)) {
            return $this->keyNotAllowed();
        }
        $value = $request->input('value');

        if (! SystemSettingsCatalog::validate($key, $value)) {
            return response()->json(['error' => [
                'code' => 'SETTING_VALUE_INVALID',
                'message' => 'Giá trị không hợp lệ theo kiểu dữ liệu của setting.',
            ]], 422);
        }

        $row = $this->svc->set($key, $value, Auth::guard('admin_web')->id());

        return response()->json(['data' => [
            'key' => $key,
            'updated_at' => $row->updated_at?->toIso8601String(),
        ]]);
    }

    public function destroy(string $key): JsonResponse
    {
        if (! SystemSettingsCatalog::has($key)) {
            return $this->keyNotAllowed();
        }
        $this->svc->forget($key);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function syncFromEnv(): JsonResponse
    {
        $adminId = Auth::guard('admin_web')->id();
        $existing = SystemSetting::query()->pluck('key')->all();
        $created = 0;

        foreach (SystemSettingsCatalog::all() as $key => $meta) {
            if (in_array($key, $existing, true)) {
                continue;
            }
            $envVal = env($meta['env']);
            if ($envVal === null || $envVal === '') {
                continue;
            }
            $this->svc->set($key, $envVal, $adminId);
            $created++;
        }

        return response()->json(['data' => ['created' => $created]]);
    }

    private function keyNotAllowed(): JsonResponse
    {
        return response()->json(['error' => [
            'code' => 'SETTING_KEY_NOT_ALLOWED',
            'message' => 'Setting key không có trong whitelist.',
        ]], 422);
    }
}
