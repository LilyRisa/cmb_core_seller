<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Admin\Http\Requests\AdminLoginRequest;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Spec 2026-05-17 — auth admin tách lập.
 *
 * Endpoint:
 *   POST /api/v1/admin/auth/login           — username + password
 *   POST /api/v1/admin/auth/logout
 *   GET  /api/v1/admin/auth/me
 *   POST /api/v1/admin/auth/change-password
 *
 * Login dùng guard `admin_web` (session). Mọi route khác `/api/v1/admin/*` dùng
 * `auth:admin` (Sanctum stateful resolve về `admin_web` session). Cookie session
 * cùng tên với user thường (chia sẻ cùng Laravel session) nhưng key login khác
 * (Laravel session lưu login_admin_web_<hash> riêng) — login user/admin song
 * song trên cùng browser không xung đột.
 */
class AdminAuthController extends Controller
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $admin = AdminUser::query()->where('username', $data['username'])->first();

        if (! $admin || ! $admin->is_active || ! Hash::check($data['password'], $admin->password)) {
            return response()->json(['error' => [
                'code' => 'ADMIN_AUTH_FAILED',
                'message' => 'Sai tài khoản hoặc mật khẩu, hoặc tài khoản đã bị vô hiệu hoá.',
            ]], 401);
        }

        Auth::guard('admin_web')->login($admin, remember: false);
        $request->session()->regenerate();

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        AuditLog::record('admin.auth.login');

        return response()->json(['data' => $this->present($admin)]);
    }

    public function logout(Request $request): JsonResponse
    {
        AuditLog::record('admin.auth.logout');
        Auth::guard('admin_web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return response()->json(['data' => $this->present($admin)]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user();
        if (! Hash::check($data['current_password'], $admin->password)) {
            return response()->json(['error' => [
                'code' => 'ADMIN_AUTH_FAILED',
                'message' => 'Mật khẩu hiện tại không đúng.',
            ]], 401);
        }
        $admin->forceFill(['password' => $data['password']])->save();
        AuditLog::record('admin.auth.change_password');

        return response()->json(['data' => ['ok' => true]]);
    }

    /** @return array<string, mixed> */
    private function present(AdminUser $a): array
    {
        return [
            'id' => $a->id,
            'username' => $a->username,
            'email' => $a->email,
            'name' => $a->name,
            'is_active' => (bool) $a->is_active,
            'last_login_at' => $a->last_login_at?->toIso8601String(),
        ];
    }
}
