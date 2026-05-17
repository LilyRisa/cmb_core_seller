<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Spec 2026-05-17 — quản lý các super-admin khác.
 *
 * Endpoint: `/api/v1/admin/admin-users/*`
 *
 * Guard rails:
 *   - CANNOT_SELF_MUTATE (409): admin không thể suspend / reset password chính mình.
 *   - LAST_ACTIVE_ADMIN (409): không thể suspend admin active cuối cùng (sau hành động
 *     sẽ không còn admin nào active → mất truy cập hệ thống).
 */
class AdminAdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));

        $query = AdminUser::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn (AdminUser $a) => $this->present($a))->all(),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->present(AdminUser::query()->findOrFail($id))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'regex:/^[a-z0-9._-]{3,32}$/', Rule::unique('admin_users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('admin_users', 'email')],
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $admin = AdminUser::create($data + ['is_active' => $data['is_active'] ?? true]);
        AuditLog::record('admin.admin_user.create', $admin, ['username' => $admin->username]);

        return response()->json(['data' => $this->present($admin)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $admin = AdminUser::query()->findOrFail($id);
        $data = $request->validate([
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('admin_users', 'email')->ignore($admin->id)],
            'name' => ['sometimes', 'string', 'max:120'],
        ]);
        $admin->fill($data)->save();
        AuditLog::record('admin.admin_user.update', $admin, ['changes' => $data]);

        return response()->json(['data' => $this->present($admin)]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        if ($conflict = $this->refuseSelf($id)) {
            return $conflict;
        }
        $admin = AdminUser::query()->findOrFail($id);
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);
        $admin->forceFill(['password' => $data['password']])->save();
        AuditLog::record('admin.admin_user.reset_password', $admin);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function suspend(int $id): JsonResponse
    {
        if ($conflict = $this->refuseSelf($id)) {
            return $conflict;
        }
        $admin = AdminUser::query()->findOrFail($id);

        if ($admin->is_active && AdminUser::query()->where('is_active', true)->count() <= 1) {
            return $this->conflict('LAST_ACTIVE_ADMIN', 'Không thể vô hiệu hoá admin đang hoạt động cuối cùng.');
        }

        $admin->forceFill(['is_active' => false])->save();
        AuditLog::record('admin.admin_user.suspend', $admin);

        return response()->json(['data' => $this->present($admin)]);
    }

    public function reactivate(int $id): JsonResponse
    {
        $admin = AdminUser::query()->findOrFail($id);
        $admin->forceFill(['is_active' => true])->save();
        AuditLog::record('admin.admin_user.reactivate', $admin);

        return response()->json(['data' => $this->present($admin)]);
    }

    private function refuseSelf(int $id): ?JsonResponse
    {
        if (Auth::guard('admin_web')->id() === $id) {
            return $this->conflict('CANNOT_SELF_MUTATE', 'Không thể thao tác trên chính tài khoản admin của bạn.');
        }

        return null;
    }

    private function conflict(string $code, string $message): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], 409);
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
            'created_at' => $a->created_at?->toIso8601String(),
        ];
    }
}
