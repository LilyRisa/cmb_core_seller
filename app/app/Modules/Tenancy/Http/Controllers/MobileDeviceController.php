<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Http\Requests\RegisterMobileDeviceRequest;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expo Push device registry — SPEC 0029.
 *
 *  - POST   /api/v1/me/devices       → upsert theo expo_push_token (an toàn gọi lại).
 *  - DELETE /api/v1/me/devices/{id}  → xoá; ownership-guarded ⇒ 404 nếu không sở hữu.
 *
 * Sống trong Tenancy module (không phải Messaging) vì đây là registry thuộc về
 * user + tenant — cùng tầng với device/token management; Messaging chỉ ĐỌC bảng
 * này khi gửi push. Yêu cầu middleware auth:sanctum + tenant.
 */
class MobileDeviceController extends Controller
{
    /**
     * POST /api/v1/me/devices — upsert device theo expo_push_token.
     */
    public function store(RegisterMobileDeviceRequest $request, CurrentTenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $device = MobileDevice::query()->firstOrNew([
            'expo_push_token' => $data['expo_push_token'],
        ]);
        $isNew = ! $device->exists;

        $device->fill([
            'tenant_id' => $tenant->id(),
            'user_id' => $request->user()->getKey(),
            'platform' => $data['platform'],
            'last_seen_at' => now(),
        ]);
        if ($isNew) {
            // Baseline: chỉ gom inbound MỚI sau khi đăng ký (tránh push dồn lịch sử).
            $device->last_notified_at = now();
        }
        $device->save();

        return response()->json([
            'data' => [
                'id' => $device->getKey(),
                'expo_push_token' => $device->expo_push_token,
                'platform' => $device->platform,
            ],
        ], 201);
    }

    /**
     * DELETE /api/v1/me/devices/{id} — xoá device của chính user trong tenant hiện tại.
     */
    public function destroy(Request $request, CurrentTenant $tenant, int $id): JsonResponse
    {
        $device = MobileDevice::query()
            ->whereKey($id)
            ->where('user_id', $request->user()->getKey())
            ->where('tenant_id', $tenant->id())
            ->first();

        if (! $device) {
            return response()->json([
                'error' => ['code' => 'DEVICE_NOT_FOUND', 'message' => 'Không tìm thấy thiết bị.'],
            ], 404);
        }

        $device->delete();

        return response()->json(null, 204);
    }
}
