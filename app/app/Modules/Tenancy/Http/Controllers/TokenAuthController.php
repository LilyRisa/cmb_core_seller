<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\Concerns\ResolvesAuthUserPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Token-based auth cho app mobile / client không-SPA (SPEC 2026-06-01).
 *
 * Bearer token (Sanctum personal access token) — KHÁC luồng SPA cookie ở
 * {@see AuthController}. Cùng guard `auth:sanctum` xác thực cả 2 loại client,
 * nên token cấp ở đây dùng được MỌI endpoint nghiệp vụ hiện có (gắn header
 * `Authorization: Bearer <token>` + `X-Tenant-Id`). Hạn token mặc định 60 ngày
 * (`config('sanctum.mobile_token_days')`); thu hồi qua quản lý thiết bị.
 */
class TokenAuthController extends Controller
{
    use ResolvesAuthUserPayload;

    /** Đăng nhập mobile: validate credentials → cấp bearer token gắn tên thiết bị. */
    public function token(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::guard('web')->validate(['email' => $data['email'], 'password' => $data['password']])) {
            return response()->json([
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Email hoặc mật khẩu không đúng.'],
            ], 422);
        }

        /** @var User $user */
        $user = User::where('email', $data['email'])->firstOrFail();

        $days = (int) config('sanctum.mobile_token_days', 60);
        $token = $user->createToken($data['device_name'], ['*'], now()->addDays($days));

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'user' => $this->userPayload($user),
            ],
        ], 201);
    }

    /** Đăng xuất mobile: thu hồi token đang dùng. */
    public function revoke(Request $request): JsonResponse
    {
        $this->currentToken($request)?->delete();

        return response()->json(null, 204);
    }

    /** Liệt kê token (thiết bị) của user — kèm cờ `current` cho token đang dùng. */
    public function devices(Request $request): JsonResponse
    {
        $currentId = $this->currentToken($request)?->getKey();

        $devices = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->getKey(),
                'device_name' => $t->name,
                'last_used_at' => optional($t->last_used_at)->toIso8601String(),
                'created_at' => optional($t->created_at)->toIso8601String(),
                'current' => $t->getKey() === $currentId,
            ])->values();

        return response()->json(['data' => $devices]);
    }

    /** Thu hồi 1 token theo id — chỉ token của chính user (ownership guard ⇒ 404 nếu không phải). */
    public function revokeDevice(Request $request, int $id): JsonResponse
    {
        $token = $request->user()->tokens()->whereKey($id)->first();
        if (! $token) {
            return response()->json([
                'error' => ['code' => 'TOKEN_NOT_FOUND', 'message' => 'Không tìm thấy thiết bị.'],
            ], 404);
        }

        $token->delete();

        return response()->json(null, 204);
    }

    /** Thu hồi mọi token TRỪ token đang dùng ("đăng xuất mọi thiết bị khác"). */
    public function revokeOthers(Request $request): JsonResponse
    {
        $currentId = $this->currentToken($request)?->getKey();

        $request->user()->tokens()
            ->when($currentId !== null, fn ($q) => $q->whereKeyNot($currentId))
            ->delete();

        return response()->json(null, 204);
    }

    /**
     * Token Sanctum đang dùng cho request hiện tại, hoặc null nếu xác thực bằng
     * session SPA (TransientToken — không có khoá chính để thao tác).
     */
    private function currentToken(Request $request): ?PersonalAccessToken
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }
}
